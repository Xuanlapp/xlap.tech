<?php

namespace App\Livewire\Pages\Drive;

use App\Models\ProductDriveUpload;
use App\Models\ProductDesignAsset;
use App\Services\Product\ProductDriveUploadQueueService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Throwable;
use Livewire\Attributes\Session;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DriveUploads extends Component
{
    use WithPagination;

    private const STATUS_OPTIONS = ['all', 'waiting', 'processing', 'completed', 'failed'];

    #[Url(as: 'status', history: true, except: 'all')]
    public string $status = 'all';

    #[Session(key: 'drive-uploads.search')]
    public string $search = '';

    public ?int $selectedUploadId = null;

    public ?string $retryMessage = null;

    public ?string $retryError = null;

    public function mount(): void
    {
        if (! in_array($this->status, self::STATUS_OPTIONS, true)) {
            $this->status = 'all';
        }
    }

    public function updatedStatus(string $status): void
    {
        $this->status = in_array($status, self::STATUS_OPTIONS, true) ? $status : 'all';
        $this->resetPage();
    }

    public function updatedSearch(string $search): void
    {
        $this->search = trim($search);
        $this->resetPage();
    }

    public function openLinks(int $uploadId): void
    {
        $upload = $this->visibleQuery()->whereKey($uploadId)->firstOrFail();
        $this->selectedUploadId = $upload->id;
    }

    public function closeLinks(): void
    {
        $this->selectedUploadId = null;
    }

    public function retryUpload(int $uploadId): void
    {
        $this->retryMessage = null;
        $this->retryError = null;

        $upload = $this->visibleQuery()->whereKey($uploadId)->firstOrFail();

        try {
            if (auth()->user()->isManager() && $upload->user_id !== auth()->id()) {
                throw new \RuntimeException('Manager chỉ được export dữ liệu của chính mình.');
            }

            $result = $this->queueFailedUpload($upload);
            if ($result === 'completed') {
                $this->retryMessage = 'Item da co du file Drive; da cap nhat trang thai completed.';
            } elseif ($result === 'queued') {
                $itemNumber = $upload->asset?->item_number ?? $upload->product_design_asset_id;
                $this->retryMessage = "Da dua item {$itemNumber} ve hang doi upload.";
            } else {
                $this->retryError = 'Item khong con anh local hoac link Drive hop le de thu lai.';
            }
        } catch (Throwable $exception) {
            $this->retryError = $exception->getMessage();
        }
    }

    public function retryAllFailed(): void
    {
        abort_unless(auth()->user()->is_admin, 403);

        $this->retryMessage = null;
        $this->retryError = null;
        $queued = 0;
        $completed = 0;
        $skipped = 0;

        ProductDriveUpload::query()
            ->where('status', 'failed')
            ->select('id', 'product_design_asset_id', 'drive_files', 'drive_folder_id')
            ->chunkById(100, function ($uploads) use (&$queued, &$completed, &$skipped): void {
                foreach ($uploads as $upload) {
                    try {
                        $result = $this->queueFailedUpload($upload);
                        if ($result === 'queued') $queued++;
                        elseif ($result === 'completed') $completed++;
                        else $skipped++;
                    } catch (Throwable $exception) {
                        $skipped++;
                        report($exception);
                    }
                }
            });

        $this->retryMessage = "Da dua {$queued} item ve hang doi; xac nhan {$completed} item da co du file Drive.";
        if ($skipped > 0) {
            $this->retryError = "Bo qua {$skipped} item khong co nguon anh hop le hoac gap loi; kiem tra truoc khi thu lai.";
        }
    }

    private function queueFailedUpload(ProductDriveUpload $upload): string|false
    {
        $asset = ProductDesignAsset::query()->find($upload->product_design_asset_id);
        if ($asset && $this->hasCompleteDriveExport($upload, $asset)) {
            ProductDriveUpload::query()->whereKey($upload->id)->update([
                'status' => 'completed', 'error' => null, 'completed_at' => now(),
            ]);
            return 'completed';
        }

        $hasDriveSource = collect(ProductDriveUploadQueueService::IMAGE_FIELDS)
            ->contains(fn (string $field): bool => $this->driveFileIdFromUrl((string) $asset?->getAttribute($field)) !== null);
        if (! $asset?->is_approved || (! app(ProductDriveUploadQueueService::class)->hasLocalExportableImages($asset) && ! $hasDriveSource && ! $upload->drive_folder_id)) {
            return false;
        }

        return ProductDriveUpload::query()
            ->whereKey($upload->id)
            ->whereIn('status', ['failed', 'waiting'])
            ->update(['status' => 'waiting', 'error' => null, 'started_at' => null, 'completed_at' => null]) === 1 ? 'queued' : false;
    }

    private function hasCompleteDriveExport(ProductDriveUpload $upload, ProductDesignAsset $asset): bool
    {
        $files = is_array($upload->drive_files) ? $upload->drive_files : [];
        if ($files === [] || ! empty($asset->redesign_candidates)) return false;
        $recorded = [];
        foreach ($files as $file) {
            $field = $file['field'] ?? null;
            if (! is_string($field) || ! in_array($field, ProductDriveUploadQueueService::IMAGE_FIELDS, true) || isset($recorded[$field])) return false;
            $recorded[$field] = $this->driveFileIdFromUrl((string) ($file['drive_url'] ?? ''));
            if (! $recorded[$field]) return false;
        }
        $assetCount = 0;
        foreach (ProductDriveUploadQueueService::IMAGE_FIELDS as $field) {
            $value = $asset->getAttribute($field);
            if (! is_string($value) || trim($value) === '') continue;
            $assetId = $this->driveFileIdFromUrl($value);
            if ($assetId === null || ($recorded[$field] ?? null) !== $assetId) return false;
            $assetCount++;
        }
        return $assetCount > 0 && $assetCount === count($recorded);
    }
    private function driveFileIdFromUrl(string $url): ?string
    {
        if (parse_url($url, PHP_URL_HOST) !== 'drive.google.com') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '';
        if (preg_match('#/file/d/([^/]+)#', $path, $matches) === 1) {
            return $matches[1];
        }

        parse_str((string) (parse_url($url, PHP_URL_QUERY) ?: ''), $params);
        return isset($params['id']) && is_string($params['id']) && trim($params['id']) !== ''
            ? trim($params['id'])
            : null;
    }

    public function render(): View
    {
        return view('livewire.pages.drive.drive-uploads', [
            'uploads' => $this->visibleQuery()
                ->latest('updated_at')
                ->paginate(15),
            'statusCounts' => $this->statusCounts(),
            'statusOptions' => self::STATUS_OPTIONS,
            'selectedUpload' => $this->selectedUpload(),
        ])->layout('layouts.app');
    }

    private function visibleQuery(): Builder
    {
        return ProductDriveUpload::query()
            ->with(['asset:id,item_number,sku,approved_at,drive_uploaded_at', 'user:id,name,email', 'product:id,name,slug'])
            ->when(! auth()->user()->is_admin && ! auth()->user()->isManager(), fn (Builder $query) => $query->where('user_id', auth()->id()))
            ->when($this->status !== 'all', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->normalizedSearch() !== null, function (Builder $query): void {
                $search = $this->normalizedSearch();

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('product_design_asset_id', ctype_digit($search) ? (int) $search : -1)
                        ->orWhereHas('asset', function (Builder $query) use ($search): void {
                            $query
                                ->when(ctype_digit($search), fn (Builder $query) => $query->where('item_number', (int) $search))
                                ->orWhere('sku', 'like', '%'.$this->escapeLike($search).'%');
                        });

                    if (auth()->user()->is_admin || auth()->user()->isManager()) {
                        $query->orWhereHas('user', function (Builder $query) use ($search): void {
                            $query
                                ->where('name', 'like', '%'.$this->escapeLike($search).'%')
                                ->orWhere('email', 'like', '%'.$this->escapeLike($search).'%');
                        });
                    }
                });
            });
    }

    /**
     * @return array{all: int, waiting: int, processing: int, completed: int, failed: int}
     */
    private function statusCounts(): array
    {
        $query = ProductDriveUpload::query()
            ->when(! auth()->user()->is_admin && ! auth()->user()->isManager(), fn (Builder $query) => $query->where('user_id', auth()->id()));

        return [
            'all' => (clone $query)->count(),
            'waiting' => (clone $query)->where('status', 'waiting')->count(),
            'processing' => (clone $query)->where('status', 'processing')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
        ];
    }

    private function selectedUpload(): ?ProductDriveUpload
    {
        if (! $this->selectedUploadId) {
            return null;
        }

        return $this->visibleQuery()
            ->whereKey($this->selectedUploadId)
            ->first();
    }

    private function normalizedSearch(): ?string
    {
        $search = trim($this->search);

        return $search === '' ? null : $search;
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\%_');
    }
}
