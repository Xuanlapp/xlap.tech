<?php

namespace App\Livewire\Pages\Drive;

use App\Models\ProductDriveUpload;
use App\Models\ProductDesignAsset;
use App\Services\Product\ProductDriveUploadQueueService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Throwable;
use Livewire\Attributes\Session;
use Livewire\Component;
use Livewire\WithPagination;

class DriveUploads extends Component
{
    use WithPagination;

    private const STATUS_OPTIONS = ['all', 'waiting', 'processing', 'completed', 'failed'];

    #[Session(key: 'drive-uploads.status')]
    public string $status = 'all';

    #[Session(key: 'drive-uploads.search')]
    public string $search = '';

    public ?int $selectedUploadId = null;

    public ?string $retryMessage = null;

    public ?string $retryError = null;

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

            if ($this->queueFailedUpload($upload)) {
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
        $skipped = 0;

        ProductDriveUpload::query()
            ->where('status', 'failed')
            ->select('id', 'product_design_asset_id', 'drive_files', 'drive_folder_id')
            ->chunkById(100, function ($uploads) use (&$queued, &$skipped): void {
                foreach ($uploads as $upload) {
                    try {
                        $this->queueFailedUpload($upload) ? $queued++ : $skipped++;
                    } catch (Throwable $exception) {
                        $skipped++;
                        report($exception);
                    }
                }
            });

        $this->retryMessage = "Da dua {$queued} item ve hang doi upload.";
        if ($skipped > 0) {
            $this->retryError = "Bo qua {$skipped} item khong co nguon anh hop le hoac gap loi; kiem tra truoc khi thu lai.";
        }
    }

    private function queueFailedUpload(ProductDriveUpload $upload): bool
    {
        $asset = ProductDesignAsset::query()->find($upload->product_design_asset_id);
        $hasDriveSource = collect(ProductDriveUploadQueueService::IMAGE_FIELDS)
            ->contains(fn (string $field): bool => preg_match('#/file/d/[^/]+#', (string) $asset?->getAttribute($field)) === 1);
        if (! $asset?->is_approved || (! app(ProductDriveUploadQueueService::class)->hasLocalExportableImages($asset) && ! $hasDriveSource && ! $upload->drive_folder_id)) {
            return false;
        }

        return ProductDriveUpload::query()
            ->whereKey($upload->id)
            ->where('status', 'failed')
            ->update(['status' => 'waiting', 'error' => null, 'started_at' => null, 'completed_at' => null]) === 1;
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
