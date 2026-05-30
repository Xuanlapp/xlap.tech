<?php

namespace App\Services\Product;

use App\Models\ProductDesignAsset;
use App\Models\User;
use App\Services\Google\GoogleDriveService;
use App\Services\Logging\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ApprovedAssetDriveExportService
{
    private const IMAGE_FIELDS = [
        'redesign',
        'lifestyle1',
        'lifestyle2',
        'lifestyle3',
        'mockup1',
        'mockup2',
        'mockup3',
        'mockup4',
        'mockup5',
        'mockup6',
        'mockup7',
        'mockup8',
        'mockup9',
        'mockup10',
        'mockup11',
    ];

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
        $assets = ProductDesignAsset::query()
            ->with('product')
            ->where('is_approved', true)
            ->where(function ($query): void {
                foreach (self::IMAGE_FIELDS as $field) {
                    $query->orWhere($field, 'like', '/storage/%');
                }

                $query->orWhereNotNull('redesign_candidates');
            })
            ->orderBy('id')
            ->get();

        $assetCount = 0;
        $imageCount = 0;

        foreach ($assets as $asset) {
            $result = $this->exportAsset($asset, $actor, $trigger);

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

    private function exportAsset(ProductDesignAsset $asset, ?User $actor, string $trigger): int
    {
        $updates = [];
        $deletePaths = [];
        $uploaded = [];

        foreach (self::IMAGE_FIELDS as $field) {
            $url = $asset->getAttribute($field);

            if (! is_string($url) || ! str_starts_with($url, '/storage/')) {
                continue;
            }

            $absolutePath = $this->absoluteStoragePath($url);

            if (! $absolutePath || ! File::exists($absolutePath)) {
                continue;
            }

            $driveUrl = $this->drive->uploadLocalFile(
                $absolutePath,
                $this->driveFilename($asset, $field, $absolutePath),
                File::mimeType($absolutePath) ?: null,
            );

            $updates[$field] = $driveUrl;
            $deletePaths[] = $absolutePath;
            $uploaded[] = [
                'field' => $field,
                'local_url' => $url,
                'drive_url' => $driveUrl,
                'filename' => $this->driveFilename($asset, $field, $absolutePath),
            ];
        }

        $candidateCleanup = $this->cleanupRedesignCandidates($asset, $actor, $trigger);

        if ($candidateCleanup['should_clear']) {
            $updates['redesign_candidates'] = null;
        }

        if ($updates === []) {
            return 0;
        }

        $updates['drive_uploaded_at'] = now();

        DB::transaction(function () use ($asset, $updates): void {
            $asset->update($updates);
        });

        foreach (array_unique([...$deletePaths, ...$candidateCleanup['delete_paths']]) as $path) {
            File::delete($path);
        }

        foreach ($uploaded as $image) {
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

    private function absoluteStoragePath(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || ! str_starts_with($path, '/storage/')) {
            return null;
        }

        return public_path(ltrim($path, '/'));
    }

    private function driveFilename(ProductDesignAsset $asset, string $field, string $absolutePath): string
    {
        $product = $asset->product?->slug ?: 'product';
        $keyword = Str::slug((string) $asset->keyword) ?: 'asset';
        $extension = pathinfo($absolutePath, PATHINFO_EXTENSION) ?: 'png';

        return "{$product}-{$asset->item_number}-{$keyword}-{$field}.{$extension}";
    }
}
