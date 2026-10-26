<?php

namespace App\Livewire\Pages\Marketplace;

use App\Livewire\Concerns\ReportsUserActionErrors;
use App\Models\ProductDesignAsset;
use App\Models\Product;
use App\Models\User;
use App\Services\Marketplace\MarketplaceListingMetadataService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Throwable;
use Livewire\Attributes\Session;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ListingMetadataStatus extends Component
{
    use ReportsUserActionErrors;
    use WithPagination;

    private const STATUS_OPTIONS = ['all', 'waiting', 'processing', 'completed', 'failed'];
    private const PAGE_PRODUCT_SLUGS = ['decal', 'suncatcher', 'suncatcher-two', 'ornament', 'ornament-etsy', 'ornament-amazon-2', 'sticker', 'glass', 'ceramic'];

    #[Url(as: 'status', history: true, except: 'all')]
    public string $status = 'all';

    #[Session(key: 'listing-metadata.search')]
    public string $search = '';

    #[Url(as: 'user_id', history: true, except: '')]
    public string $userId = '';

    #[Url(as: 'product_id', history: true, except: '')]
    public string $productId = '';

    public ?string $retryMessage = null;

    public ?string $retryError = null;

    /** @var array<int, bool> */
    public array $selectedAssets = [];

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

    public function refreshLogs(): void
    {
        Cache::forget($this->statusCountsCacheKey());
    }

    public function updatedSearch(string $search): void
    {
        $this->search = trim($search);
        $this->resetPage();
    }

    public function updatedUserId(string $userId): void
    {
        abort_unless(auth()->user()->is_admin || auth()->user()->isManager(), 403);

        $this->userId = $userId !== '' && ctype_digit($userId) && User::query()->whereKey((int) $userId)->exists()
            ? (string) (int) $userId
            : '';
        if ($this->productId !== '' && ! $this->availableRetryProducts()->contains('id', (int) $this->productId)) {
            $this->productId = '';
        }
        $this->resetPage();
    }

    public function updatedProductId(string $productId): void
    {
        abort_unless(auth()->user()->is_admin || auth()->user()->isManager(), 403);

        $this->productId = $productId !== '' && ctype_digit($productId)
            && $this->availableRetryProducts()->contains('id', (int) $productId)
                ? (string) (int) $productId
                : '';
        $this->resetPage();
    }

    public function retryListing(int $assetId): void
    {
        $this->retryMessage = null;
        $this->retryError = null;

        try {
            $result = app(MarketplaceListingMetadataService::class)->queueRetryApprovedAsset(
                $this->baseQuery()->whereKey($assetId)->firstOrFail()->id,
            );
            $this->retryMessage = $result->marketplace_listing_status === 'completed'
                ? "Item #{$assetId} da du metadata; da sua trang thai Completed, khong goi lai AI."
                : "Da dua item #{$assetId} ve hang doi. Worker se tu xu ly.";
            $this->refreshLogs();
        } catch (Throwable $exception) {
            $this->reportUserActionError($exception, 'marketplace.queue_retry_listing', ['asset_id' => $assetId]);
            $this->retryError = $exception->getMessage();
        }
    }

    public function retrySelected(): void
    {
        abort_unless(auth()->user()->is_admin || auth()->user()->isManager(), 403);

        $this->retryMessage = null;
        $this->retryError = null;
        $this->userId = $this->normalizeUserId($this->userId);
        $this->productId = $this->normalizeProductId($this->productId);
        $ids = array_keys(array_filter($this->selectedAssets));
        $queued = 0;

        foreach ($ids as $assetId) {
            try {
                app(MarketplaceListingMetadataService::class)->queueRetryApprovedAsset(
                    $this->retryQuery()->whereKey((int) $assetId)->firstOrFail()->id,
                );
                $queued++;
            } catch (Throwable $exception) {
                $this->reportUserActionError($exception, 'marketplace.queue_retry_listing', ['asset_id' => $assetId]);
            }
        }

        $this->selectedAssets = [];
        $this->refreshLogs();
        $this->retryMessage = $queued > 0
            ? "Da dua {$queued} item ve hang doi. Worker se tu xu ly."
            : 'Khong co item nao duoc dua ve hang doi.';
    }

    public function retryAllFailed(): void
    {
        abort_unless(auth()->user()->is_admin, 403);

        $this->retryMessage = null;
        $this->retryError = null;
        $queued = 0;
        $failed = 0;
        $service = app(MarketplaceListingMetadataService::class);
        $this->userId = $this->normalizeUserId($this->userId);
        $this->productId = $this->normalizeProductId($this->productId);

        $this->applyFailedMetadataFilter($this->retryQuery())
            ->select('id')
            ->chunkById(100, function ($assets) use ($service, &$queued, &$failed): void {
                foreach ($assets as $asset) {
                    try {
                        if ($service->queueRetryApprovedAsset($asset->id)->marketplace_listing_status === 'waiting') {
                            $queued++;
                        }
                    } catch (Throwable $exception) {
                        $failed++;
                        $this->reportUserActionError($exception, 'marketplace.queue_retry_all', ['asset_id' => $asset->id]);
                    }
                }
            });

        $this->selectedAssets = [];
        $this->refreshLogs();
        $this->retryMessage = "Da dua {$queued} item loi ve hang doi. Worker se tu xu ly.";
        if ($failed > 0) {
            $this->retryError = "Co {$failed} item khong the dua ve hang doi. Kiem tra log de biet chi tiet.";
        }
    }

    public function render(): View
    {
        return view('livewire.pages.marketplace.listing-metadata-status', [
            'assets' => $this->baseQuery()
                ->latest('approved_at')
                ->latest('id')
                ->paginate(15),
            'statusCounts' => $this->statusCounts(),
            'statusOptions' => self::STATUS_OPTIONS,
            'retryFailedCount' => (auth()->user()->is_admin || auth()->user()->isManager())
                ? $this->applyFailedMetadataFilter($this->retryQuery())->count()
                : 0,
            'retryUserName' => $this->userId !== ''
                ? User::query()->whereKey((int) $this->userId)->value('name')
                : null,
            'retryUsers' => (auth()->user()->is_admin || auth()->user()->isManager())
                ? User::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'retryProducts' => (auth()->user()->is_admin || auth()->user()->isManager())
                ? $this->availableRetryProducts() : collect(),
        ])->layout('layouts.app');
    }

    private function baseQuery(): Builder
    {
        return ProductDesignAsset::query()
            ->with(['user:id,name,email,can_generate_amazon_listing,can_generate_etsy_listing', 'product:id,name,slug'])
            ->where('is_approved', true)
            ->when(! auth()->user()->is_admin && ! auth()->user()->isManager(), fn (Builder $query) => $query->where('user_id', auth()->id()))
            ->when((auth()->user()->is_admin || auth()->user()->isManager()) && $this->userId !== '', fn (Builder $query) => $query->where('user_id', (int) $this->userId))
            ->when((auth()->user()->is_admin || auth()->user()->isManager()) && $this->productId !== '', fn (Builder $query) => $query->where('product_id', (int) $this->productId))
            ->when($this->normalizedSearch() !== null, function (Builder $query): void {
                $search = $this->normalizedSearch();

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('sku', 'like', '%'.$this->escapeLike($search).'%')
                        ->orWhere('item_number', ctype_digit($search) ? (int) $search : -1)
                        ->orWhere('title', 'like', '%'.$this->escapeLike($search).'%');

                    if (auth()->user()->is_admin || auth()->user()->isManager()) {
                        $query->orWhereHas('user', function (Builder $query) use ($search): void {
                            $query
                                ->where('email', 'like', '%'.$this->escapeLike($search).'%')
                                ->orWhere('name', 'like', '%'.$this->escapeLike($search).'%');
                        });
                    }
                });
            })
            ->when($this->status !== 'all', fn (Builder $query) => $this->applyStatusFilter($query, $this->status));
    }

    private function retryQuery(): Builder
    {
        abort_unless(auth()->user()->is_admin || auth()->user()->isManager(), 403);

        return ProductDesignAsset::query()
            ->where('is_approved', true)
            ->whereHas('product', fn (Builder $product) => $product->whereIn('slug', self::PAGE_PRODUCT_SLUGS))
            ->when($this->userId !== '', fn (Builder $query) => $query->where('user_id', (int) $this->userId))
            ->when($this->normalizeProductId($this->productId) !== '', fn (Builder $query) => $query->where('product_id', (int) $this->normalizeProductId($this->productId)));
    }

    private function availableRetryProducts(): \Illuminate\Support\Collection
    {
        $query = Product::query()->where('is_active', true)->whereIn('slug', self::PAGE_PRODUCT_SLUGS);
        if ($this->userId !== '') {
            $retryUser = User::query()->find((int) $this->userId);
            if ($retryUser && ! $retryUser->isManager()) {
                $query->whereHas('users', fn (Builder $users) => $users->whereKey($retryUser->id));
            }
        }

        return $query->orderBy('name')->get(['id', 'name', 'slug']);
    }
    private function normalizeProductId(string $productId): string
    {
        if ($productId === '') {
            return '';
        }

        abort_unless(ctype_digit($productId)
            && $this->availableRetryProducts()->contains('id', (int) $productId), 403);

        return (string) (int) $productId;
    }

    private function normalizeUserId(string $userId): string
    {
        return $userId !== '' && ctype_digit($userId) && User::query()->whereKey((int) $userId)->exists()
            ? (string) (int) $userId
            : '';
    }

    private function applyStatusFilter(Builder $query, string $status): Builder
    {
        return match ($status) {
            'waiting' => $this->applyWaitingMetadataFilter($query),
            'processing' => $query->where('marketplace_listing_status', 'processing'),
            'completed' => $this->applyCompletedMetadataFilter($query),
            'failed' => $this->applyFailedMetadataFilter($query),
            default => $query,
        };
    }

    /**
     * @return array{all: int, waiting: int, processing: int, completed: int, failed: int}
     */
    private function statusCounts(): array
    {
        return Cache::remember($this->statusCountsCacheKey(), now()->addSeconds(10), fn (): array => $this->queryStatusCounts());
    }

    private function statusCountsCacheKey(): string
    {
        return 'listing-metadata:counts:'.(auth()->user()->is_admin || auth()->user()->isManager() ? 'staff' : 'user:'.auth()->id()).':filter:'.$this->userId.':'.$this->productId;
    }

    private function queryStatusCounts(): array
    {
        $query = ProductDesignAsset::query()
            ->where('is_approved', true)
            ->when(! auth()->user()->is_admin && ! auth()->user()->isManager(), fn (Builder $query) => $query->where('user_id', auth()->id()))
            ->when((auth()->user()->is_admin || auth()->user()->isManager()) && $this->userId !== '', fn (Builder $query) => $query->where('user_id', (int) $this->userId))
            ->when((auth()->user()->is_admin || auth()->user()->isManager()) && $this->productId !== '', fn (Builder $query) => $query->where('product_id', (int) $this->productId));

        return [
            'all' => (clone $query)->count(),
            'waiting' => $this->applyWaitingMetadataFilter(clone $query)->count(),
            'processing' => (clone $query)->where('marketplace_listing_status', 'processing')->count(),
            'completed' => $this->applyCompletedMetadataFilter(clone $query)->count(),
            'failed' => $this->applyFailedMetadataFilter(clone $query)->count(),
        ];
    }

    private function applyWaitingMetadataFilter(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('marketplace_listing_status', 'waiting')
                ->orWhere(function (Builder $query): void {
                    $query->whereNull('marketplace_listing_status')->whereNull('title');
                });
        });
    }

    private function applyCompletedMetadataFilter(Builder $query): Builder
    {
        $amazonFields = ['title', 'description', 'item_highlight', 'bullet_point_1', 'bullet_point_2', 'bullet_point_3', 'bullet_point_4', 'bullet_point_5', 'generic_keyword'];

        return $query
            ->where('marketplace_listing_status', 'completed')
            ->where(function (Builder $query) use ($amazonFields): void {
                $query->where(function (Builder $query) use ($amazonFields): void {
                    $query->where('marketplace_listing_marketplace', 'amazon');
                    foreach ($amazonFields as $field) {
                        $query->whereNotNull($field)->where($field, '!=', '');
                    }
                })->orWhere(function (Builder $query): void {
                    $query->where('marketplace_listing_marketplace', 'etsy')
                        ->whereNotNull('title')->where('title', '!=', '')
                        ->whereNotNull('description')->where('description', '!=', '')
                        ->whereNotNull('tags')->where('tags', '!=', '');
                });
            });
    }

    private function applyFailedMetadataFilter(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('marketplace_listing_status', 'failed')
                ->orWhere(function (Builder $query): void {
                    $query->where('marketplace_listing_status', 'completed')
                        ->where(function (Builder $query): void {
                            $this->applyIncompleteAmazonMetadata($query)
                                ->orWhere(function (Builder $query): void {
                                    $query->where('marketplace_listing_marketplace', 'etsy')
                                        ->where(function (Builder $query): void {
                                            $query->whereNull('title')->orWhere('title', '')
                                                ->orWhereNull('description')->orWhere('description', '')
                                                ->orWhereNull('tags')->orWhere('tags', '');
                                        });
                                });
                        });
                });
        });
    }

    private function applyIncompleteAmazonMetadata(Builder $query): Builder
    {
        return $query->where('marketplace_listing_marketplace', 'amazon')
            ->where(function (Builder $query): void {
                foreach (['title', 'description', 'item_highlight', 'bullet_point_1', 'bullet_point_2', 'bullet_point_3', 'bullet_point_4', 'bullet_point_5', 'generic_keyword'] as $field) {
                    $query->orWhereNull($field)->orWhere($field, '');
                }
            });
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
