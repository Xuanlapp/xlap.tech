<?php

namespace App\Services\Product;

use App\Models\ProductDesignAsset;
use App\Models\ProductDriveUpload;
use App\Models\User;
use App\Services\Google\GoogleDriveService;
use App\Services\Logging\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ApprovedAssetDriveExportService
{
    private const STALE_PROCESSING_MINUTES = 60;

    public function __construct(
        private readonly GoogleDriveService $drive,
        private readonly ActivityLogService $activityLogs,
    ) {}

    /**
     * Upload local images from approved assets to Google Drive and replace DB URLs.
     *
     * @return array{assets: int, images: int}
     */
    public function exportApprovedImages(?User $actor = null, string $trigger = 'scheduled'): array
    {
        $this->markStaleProcessingUploadsAsFailed();

        $assets = ProductDesignAsset::query()
            ->with(['product', 'user'])
            ->where('is_approved', true)
            ->where(function ($query): void {
                foreach (ProductDriveUploadQueueService::IMAGE_FIELDS as $field) {
                    $query->orWhere($field, 'like', '%/storage/%');
                }

                $query->orWhereNotNull('redesign_candidates');
            })
            ->orderBy('id')
            ->get();

        $assetCount = 0;
        $imageCount = 0;

        foreach ($assets as $asset) {
            try {
                $result = $this->exportAsset($asset, $actor, $trigger);
            } catch (\Throwable $exception) {
                $this->uploadRecord($asset)->update([
                    'status' => 'failed',
                    'error' => mb_substr($exception->getMessage(), 0, 2000),
                    'completed_at' => now(),
                ]);

                continue;
            }

            if ($result > 0) {
                $assetCount++;
                $imageCount += $result;
            }
        }

        $summary = ['assets' => $assetCount, 'images' => $imageCount];

        $this->activityLogs->record(
            event: 'drive_export.completed',
            description: "Uploaded {$imageCount} approved images from {$assetCount} assets to Google Drive.",
            properties: ['trigger' => $trigger, ...$summary],
            actor: $actor,
            actorType: $trigger === 'manual' ? 'admin' : 'system',
        );

        return $summary;
    }

    /**
     * Claim and upload exactly one database-backed waiting record.
     *
     * @return array{claimed: bool, assets: int, images: int}
     */
    public function exportNextWaitingUpload(?User $actor = null, string $trigger = 'scheduled'): array
    {
        $this->markStaleProcessingUploadsAsFailed();
        $this->markWaitingUploadsWithoutSourceAsFailed();

        $upload = DB::transaction(function (): ?ProductDriveUpload {
            $upload = ProductDriveUpload::query()
                ->where('status', 'waiting')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $upload) {
                return null;
            }

            $upload->update([
                'status' => 'processing',
                'error' => null,
                'started_at' => now(),
                'completed_at' => null,
            ]);

            return $upload;
        });

        if (! $upload) {
            return ['claimed' => false, 'assets' => 0, 'images' => 0];
        }

        $asset = ProductDesignAsset::query()
            ->with(['product', 'user'])
            ->find($upload->product_design_asset_id);

        if (! $asset || ! $asset->is_approved) {
            $upload->update([
                'status' => 'failed',
                'error' => 'Item khong ton tai hoac chua duyet.',
                'completed_at' => now(),
            ]);

            return ['claimed' => true, 'assets' => 0, 'images' => 0];
        }

        try {
            $images = $this->exportAsset($asset, $actor, $trigger, alreadyClaimed: true);
        } catch (\Throwable $exception) {
            ProductDriveUpload::query()->whereKey($upload->id)->where('status', 'processing')->update([
                'status' => 'failed',
                'error' => mb_substr($exception->getMessage(), 0, 2000),
                'completed_at' => now(),
            ]);

            throw $exception;
        }

        return ['claimed' => true, 'assets' => $images > 0 ? 1 : 0, 'images' => $images];
    }

    public function exportAssetById(int $assetId, ?User $actor = null, string $trigger = 'manual'): int
    {
        $this->markStaleProcessingUploadsAsFailed();

        $asset = ProductDesignAsset::query()
            ->with(['product', 'user'])
            ->findOrFail($assetId);

        if (! $asset->is_approved) {
            throw new \RuntimeException('Item nay chua duyet nen khong the upload Drive.');
        }

        return $this->exportAsset($asset, $actor, $trigger);
    }

    private function exportAsset(ProductDesignAsset $asset, ?User $actor, string $trigger, bool $alreadyClaimed = false): int
    {
        $upload = $this->uploadRecord($asset);
        if (! $alreadyClaimed && ! $this->claimUpload($upload)) {
            return 0;
        }

        // Another worker may have completed this item while this invocation was
        // waiting for its claim. Reload before looking for local source URLs.
        $asset = ProductDesignAsset::query()
            ->with(['product', 'user'])
            ->findOrFail($asset->id);
        $updates = [];
        $deletePaths = [];
        $uploaded = [];
        $fileInfo = [];
        $imageNumber = 1;

        $previousFiles = collect($upload->drive_files ?: [])->keyBy('field');
        if (! $this->assetHasAnyPersistedSource($asset, $previousFiles)) {
            $upload->update([
                'status' => 'failed',
                'error' => 'Khong tim thay anh local, link Drive hoac file Drive da luu cho item nay.',
                'completed_at' => now(),
            ]);

            return 0;
        }

        $driveFolder = $this->drive->createUploadFolder($this->folderNameForUser($asset), (string) $asset->id);
        // Every export/reupload gets a fresh folder. Do not inspect or reuse the
        // previous folder: it may contain an older mockup batch with duplicate slots.
        $folderByItem = [];

        foreach (ProductDriveUploadQueueService::IMAGE_FIELDS as $field) {
            $url = $asset->getAttribute($field);

            $folderFile = $folderByItem[$imageNumber] ?? null;
            $oldFile = $previousFiles->get($field);
            $oldDriveUrl = is_array($oldFile) && ! empty($oldFile['drive_url'])
                ? (string) $oldFile['drive_url']
                : '';

            // Try every persisted source for this slot before declaring it missing:
            // current asset URL, previous upload record, then the Drive folder file.
            if (! is_string($url) || trim($url) === '') {
                $url = $oldDriveUrl;
            }

            if (trim((string) $url) === '' && ! $folderFile) {
                continue;
            }

            if (! $this->isLocalStorageUrl($url)) {
                $oldUrl = $oldDriveUrl !== '' ? $oldDriveUrl : (string) $url;
                $sourceId = $folderFile['id'] ?? null;
                if (! $sourceId && preg_match('#/file/d/([^/]+)#', $oldUrl, $matches)) {
                    $sourceId = $matches[1];
                }
                if (! $sourceId) {
                    throw new \RuntimeException("Khong xac dinh duoc Drive file ID cho {$field}.");
                }

                $filename = (string) ($folderFile['name'] ?? (is_array($oldFile) ? ($oldFile['filename'] ?? "{$asset->id}_{$field}.png") : "{$asset->id}_{$field}.png"));
                $upload->update(['started_at' => now()]);
                $driveUrl = $this->drive->copyFileToFolder($sourceId, $filename, $driveFolder['id']);
                $updates[$field] = $driveUrl;
                $fileInfo[] = ['item' => 'item'.$imageNumber, 'field' => $field, 'filename' => $filename];
                $uploaded[] = [
                    'item' => 'item'.$imageNumber,
                    'field' => $field,
                    'drive_url' => $driveUrl,
                    'preview_url' => $this->drivePreviewUrl($driveUrl),
                    'filename' => $filename,
                ];
                $imageNumber++;
                continue;
            }

            $absolutePath = $this->absoluteStoragePath($url);

            if (! $absolutePath || ! File::exists($absolutePath)) {
                if (! $folderFile) {
                    throw new \RuntimeException("Khong tim thay file local cho {$field}.");
                }
                $filename = (string) $folderFile['name'];
                $upload->update(['started_at' => now()]);
                $driveUrl = $this->drive->copyFileToFolder($folderFile['id'], $filename, $driveFolder['id']);
                $updates[$field] = $driveUrl;
                $fileInfo[] = ['item' => 'item'.$imageNumber, 'field' => $field, 'filename' => $filename];
                $uploaded[] = [
                    'item' => 'item'.$imageNumber,
                    'field' => $field,
                    'drive_url' => $driveUrl,
                    'preview_url' => $this->drivePreviewUrl($driveUrl),
                    'filename' => $filename,
                ];
                $imageNumber++;
                continue;
            }

            $filename = $this->driveFilename($asset, $imageNumber, $absolutePath);
            $mimeType = File::mimeType($absolutePath) ?: null;
            // Read local metadata before uploading; Syncthing may remove the source immediately after upload.
            $bytes = File::size($absolutePath);
            $upload->update(['started_at' => now()]);
            $driveUrl = $this->drive->uploadLocalFile(
                $absolutePath,
                $filename,
                $mimeType,
                $driveFolder['id'],
            );

            $updates[$field] = $driveUrl;
            $deletePaths[] = $absolutePath;
            $fileInfo[] = [
                'item' => 'item'.$imageNumber,
                'field' => $field,
                'local_url' => $url,
                'filename' => $filename,
                'mime_type' => $mimeType,
                'bytes' => $bytes,
            ];
            $uploaded[] = [
                'item' => 'item'.$imageNumber,
                'field' => $field,
                'local_url' => $url,
                'drive_url' => $driveUrl,
                'preview_url' => $this->drivePreviewUrl($driveUrl),
                'filename' => $filename,
            ];
            $imageNumber++;
        }

        $candidateCleanup = $this->cleanupRedesignCandidates($asset, $actor, $trigger);

        if ($candidateCleanup['should_clear']) {
            $updates['redesign_candidates'] = null;
        }

        if ($updates === []) {
            $upload->update([
                'status' => 'failed',
                'error' => 'Khong tim thay file co the upload hoac copy.',
                'completed_at' => now(),
            ]);

            return 0;
        }

        $updates['drive_uploaded_at'] = now();

        DB::transaction(function () use ($asset, $upload, $updates, $fileInfo, $uploaded, $driveFolder): void {
            $lockedUpload = ProductDriveUpload::query()->lockForUpdate()->findOrFail($upload->id);
            if ($lockedUpload->status !== 'processing') {
                throw new \RuntimeException('Luot upload da het han hoac duoc worker khac nhan; khong ghi de link Drive.');
            }

            $asset->update($updates);
            $lockedUpload->update([
                'status' => 'completed',
                'file_info' => $fileInfo,
                'drive_files' => $uploaded,
                'drive_folder_id' => $driveFolder['id'],
                'drive_folder_link' => $driveFolder['link'],
                'error' => null,
                'completed_at' => now(),
            ]);
        });

        foreach (array_unique([...$deletePaths, ...$candidateCleanup['delete_paths']]) as $path) {
            try {
                File::delete($path);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        foreach ($uploaded as $image) {
            try {
                $this->activityLogs->record(
                    event: 'drive_export.image_uploaded',
                    description: "Uploaded approved {$image['field']} image to Google Drive.",
                    subject: $asset,
                    properties: [
                        'trigger' => $trigger,
                        'product' => $asset->product?->slug,
                        'item_number' => $asset->item_number,
                        ...$image,
                    ],
                    actor: $actor,
                    actorType: $trigger === 'manual' ? 'admin' : 'system',
                );
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return count($uploaded);
    }

    /**
     * Delete local generated master candidates after the approved asset is exported.
     *
     * @return array{should_clear: bool, deleted: int, delete_paths: array<int, string>}
     */
    private function cleanupRedesignCandidates(ProductDesignAsset $asset, ?User $actor, string $trigger): array
    {
        $deletePaths = [];
        $deleted = 0;
        $candidates = $asset->redesign_candidates ?: [];

        foreach ($candidates as $index => $url) {
            if (! is_string($url) || ! str_starts_with($url, '/storage/')) {
                continue;
            }

            $absolutePath = $this->absoluteStoragePath($url);

            if (! $absolutePath || ! File::exists($absolutePath)) {
                continue;
            }

            $deletePaths[] = $absolutePath;
            $deleted++;

            $this->activityLogs->record(
                event: 'drive_export.redesign_candidate_deleted',
                description: 'Deleted local redesign candidate after Drive export.',
                subject: $asset,
                properties: [
                    'trigger' => $trigger,
                    'product' => $asset->product?->slug,
                    'item_number' => $asset->item_number,
                    'candidate_index' => $index,
                    'local_url' => $url,
                ],
                actor: $actor,
                actorType: $trigger === 'manual' ? 'admin' : 'system',
            );
        }

        return [
            'should_clear' => $candidates !== [],
            'deleted' => $deleted,
            'delete_paths' => $deletePaths,
        ];
    }

    private function isLocalStorageUrl(mixed $url): bool
    {
        if (! is_string($url) || trim($url) === '') {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH);
        return is_string($path) && str_starts_with($path, '/storage/');
    }

    private function absoluteStoragePath(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || ! str_starts_with($path, '/storage/')) {
            return null;
        }

        return public_path(ltrim($path, '/'));
    }

    private function uploadRecord(ProductDesignAsset $asset): ProductDriveUpload
    {
        return ProductDriveUpload::query()->firstOrCreate(
            ['product_design_asset_id' => $asset->id],
            [
                'user_id' => $asset->user_id,
                'product_id' => $asset->product_id,
                'status' => 'waiting',
            ],
        );
    }

    private function claimUpload(ProductDriveUpload $upload): bool
    {
        return DB::transaction(function () use ($upload): bool {
            $lockedUpload = ProductDriveUpload::query()
                ->lockForUpdate()
                ->find($upload->id);

            if (! $lockedUpload || $lockedUpload->status === 'processing') {
                return false;
            }

            $lockedUpload->update([
                'status' => 'processing',
                'error' => null,
                'started_at' => now(),
                'completed_at' => null,
            ]);

            return true;
        });
    }

    public function markStaleProcessingUploadsAsFailed(): int
    {
        $cutoff = now()->subMinutes(self::STALE_PROCESSING_MINUTES);

        return ProductDriveUpload::query()
            ->where('status', 'processing')
            ->whereNotNull('started_at')
            ->where('started_at', '<=', $cutoff)
            ->update([
                'status' => 'failed',
                'error' => 'Upload bi treo qua lau. Hay bam Reupload de chay lai.',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function markWaitingUploadsWithoutSourceAsFailed(): int
    {
        $count = 0;

        ProductDriveUpload::query()
            ->where('status', 'waiting')
            ->with('asset')
            ->chunkById(100, function ($uploads) use (&$count): void {
                foreach ($uploads as $upload) {
                    $asset = $upload->asset;
                    if (! $asset || ! $asset->is_approved) {
                        continue;
                    }

                    $hasLocal = app(ProductDriveUploadQueueService::class)->hasLocalExportableImages($asset);
                    $hasDrive = $this->assetHasDriveImages($asset) || filled($upload->drive_folder_id);
                    if ($hasLocal || $hasDrive) {
                        continue;
                    }

                    $upload->update([
                        'status' => 'failed',
                        'error' => 'Khong con anh local va chua co link Drive hop le. Hay tao lai/giu lai anh local roi Reupload.',
                        'completed_at' => now(),
                    ]);
                    $count++;
                }
            });

        return $count;
    }

    private function assetHasAnyPersistedSource(ProductDesignAsset $asset, $previousFiles): bool
    {
        if ($this->hasLocalAssetImage($asset)) {
            return true;
        }

        foreach ($previousFiles as $file) {
            if (is_array($file) && preg_match('#/file/d/[^/]+#', (string) ($file['drive_url'] ?? '')) === 1) {
                return true;
            }
        }

        return $this->assetHasDriveImages($asset);
    }

    private function hasLocalAssetImage(ProductDesignAsset $asset): bool
    {
        foreach (ProductDriveUploadQueueService::IMAGE_FIELDS as $field) {
            if ($this->isLocalStorageUrl($asset->getAttribute($field))) {
                return true;
            }
        }

        foreach (($asset->redesign_candidates ?: []) as $url) {
            if ($this->isLocalStorageUrl($url)) {
                return true;
            }
        }

        return false;
    }

    private function assetHasDriveImages(ProductDesignAsset $asset): bool
    {
        if ($asset->drive_uploaded_at !== null) {
            return true;
        }

        foreach (ProductDriveUploadQueueService::IMAGE_FIELDS as $field) {
            $url = $asset->getAttribute($field);

            if (is_string($url) && str_contains($url, 'drive.google.com')) {
                return true;
            }
        }

        return false;
    }

    private function folderNameForUser(ProductDesignAsset $asset): string
    {
        $user = $asset->user;
        $name = preg_replace('/[^a-z0-9]+/', '', Str::lower(Str::ascii((string) $user?->name))) ?? '';

        return $name !== '' ? $name : 'user-'.$asset->user_id;
    }

    private function driveFilename(ProductDesignAsset $asset, int $imageNumber, string $absolutePath): string
    {
        $userName = $this->folderNameForUser($asset);
        $extension = pathinfo($absolutePath, PATHINFO_EXTENSION) ?: 'png';

        return "{$asset->id}_item{$imageNumber}_{$userName}.{$extension}";
    }

    private function drivePreviewUrl(string $driveUrl): string
    {
        $path = parse_url($driveUrl, PHP_URL_PATH) ?: '';
        $query = parse_url($driveUrl, PHP_URL_QUERY) ?: '';

        if (preg_match('#/file/d/([^/]+)#', $path, $matches) === 1) {
            return 'https://drive.google.com/thumbnail?id='.rawurlencode($matches[1]).'&sz=w300';
        }

        parse_str($query, $params);

        if (! empty($params['id']) && is_string($params['id'])) {
            return 'https://drive.google.com/thumbnail?id='.rawurlencode($params['id']).'&sz=w300';
        }

        return $driveUrl;
    }
}
