<?php

namespace App\Livewire\Pages\Admin;

use App\Models\Product;
use App\Models\ProductDesignAsset;
use App\Models\User;
use App\Services\Glass\GlassService;
use App\Services\Ceramic\CeramicService;
use App\Services\Sticker\StickerService;
use App\Services\Decal\DecalService;
use App\Services\Marketplace\MarketplaceListingMetadataService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class DataSkuSearch extends Component
{
    use WithPagination;

    public string $skuSearch = '';
    public string $filterMode = 'all';
    public ?int $userId = null;
    public ?int $productId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    public function updatedSkuSearch(): void { $this->resetPage(); }
    public function updatedUserId(): void { $this->resetPage(); }
    public function updatedProductId(): void { $this->resetPage(); }

    public function updatedFilterMode(): void
    {
        if ($this->filterMode === 'all') {
            $this->userId = null;
            $this->productId = null;
        } elseif ($this->filterMode === 'user') {
            $this->productId = null;
        } elseif ($this->filterMode === 'product') {
            $this->userId = null;
        }

        $this->resetPage();
    }

    public function removeSku(string $sku): void
    {
        $remaining = collect($this->normalizedSkus())
            ->reject(fn (string $current): bool => strcasecmp($current, trim($sku)) === 0)
            ->implode(',');

        $this->skuSearch = $remaining;
        $this->resetPage();
    }
    public function toggleUserFilter(int $id): void
    {
        if ($this->filterMode === 'user' && $this->userId === $id) {
            $this->filterMode = 'all';
            $this->userId = null;
        } else {
            $this->filterMode = 'user';
            $this->userId = $id;
        }
        $this->resetPage();
    }

    public function approveAllFilteredAssets(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $skus = $this->normalizedSkus();
        if ($skus === []) return;

        $services = ['glass' => GlassService::class, 'ceramic' => CeramicService::class, 'sticker' => StickerService::class, 'decal' => DecalService::class];
        $approved = 0;
        $skipped = 0;
        $this->resultsQuery($skus)->where('is_approved', false)->select('id')->chunkById(100, function ($assets) use ($services, &$approved, &$skipped): void {
            foreach ($assets as $asset) {
                $asset = ProductDesignAsset::query()->find($asset->id);
                $service = $services[$asset?->product?->slug] ?? null;
                if (! $asset || $asset->is_approved || ! $asset->user || ! $service || $asset->hasCustomMockupOutput() === false || $asset->getCustomMockupCount() < 3) {
                    $skipped++;
                    continue;
                }
                try {
                    app($service)->toggleApproval($asset->user, $asset->id);
                    $approved++;
                    app(MarketplaceListingMetadataService::class)->queueRetryApprovedAsset($asset->id);
                } catch (\Throwable $exception) {
                    report($exception);
                    if (! ProductDesignAsset::query()->whereKey($asset->id)->where('is_approved', true)->exists()) $skipped++;
                }
            }
        });

        $message = "Da duyet {$approved} item";
        if ($skipped > 0) $message .= "; bo qua {$skipped} item khong du dieu kien hoac can duyet tai trang san pham";
        $this->dispatch('notify', type: 'success', message: $message.'.');
    }

    public function resetAllFilteredAssets(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $skus = $this->normalizedSkus();
        if ($skus === []) {
            return;
        }

        $query = $this->resultsQuery($skus)->where('is_approved', true);
        $count = 0;

        $query->select('id')->chunkById(100, function ($assets) use (&$count): void {
            foreach ($assets as $asset) {
                $this->resetAssetToUnapproved((int) $asset->id, false);
                $count++;
            }
        });

        $this->dispatch('notify', type: 'success', message: "Da dua {$count} item ve chua duyet va xoa listing metadata. Anh van duoc giu nguyen.");
    }
    public function resetAssetToUnapproved(int $assetId, bool $notify = true): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $asset = ProductDesignAsset::query()->where('is_approved', true)->findOrFail($assetId);
        $asset->forceFill([
            'is_approved' => false,
            'approved_at' => null,
            'title' => null,
            'description' => null,
            'item_highlight' => null,
            'bullet_point_1' => null,
            'bullet_point_2' => null,
            'bullet_point_3' => null,
            'bullet_point_4' => null,
            'bullet_point_5' => null,
            'generic_keyword' => null,
            'tags' => null,
            'marketplace_listing_status' => null,
            'marketplace_listing_attempts' => 0,
            'marketplace_listing_started_at' => null,
            'marketplace_listing_completed_at' => null,
            'marketplace_listing_error' => null,
            'marketplace_exported_at' => null,
            'marketplace_export_filename' => null,
        ])->save();

        if ($notify) {
            $this->dispatch('notify', type: 'success', message: 'Da dua item ve chua duyet va xoa metadata listing. Anh van duoc giu nguyen.');
        }
    }
    public function clearSearch(): void
    {
        $this->skuSearch = '';
        $this->filterMode = 'all';
        $this->userId = null;
        $this->productId = null;
        $this->resetPage();
    }

    public function render(): View
    {
        $skus = $this->normalizedSkus();

        return view('livewire.pages.admin.data-sku-search', [
            'skus' => $skus,
            'users' => User::query()->select('id', 'name')->orderBy('name')->get(),
            'products' => Product::query()->whereIn('slug', $this->pageProductSlugs())->where('is_active', true)->select('id', 'name', 'slug')->orderBy('name')->get(),
            'filteredUsers' => $skus === [] ? collect() : $this->resultsQuery($skus, false)->where('is_approved', false)->with('user:id,name')->get()->pluck('user')->filter()->unique('id')->sortBy('name')->values(),
            'approvedAssets' => $skus === []
                ? ProductDesignAsset::query()->whereRaw('1 = 0')->paginate(25, ['*'], 'approvedPage')
                : $this->resultsQuery($skus)->where('is_approved', true)->paginate(25, ['*'], 'approvedPage'),
            'unapprovedAssets' => $skus === []
                ? ProductDesignAsset::query()->whereRaw('1 = 0')->paginate(25, ['*'], 'unapprovedPage')
                : $this->resultsQuery($skus)->where('is_approved', false)->paginate(25, ['*'], 'unapprovedPage'),
        ])->layout('layouts.app');
    }

    /** @return list<string> */
    private function normalizedSkus(): array
    {
        return collect(preg_split('/[,\n]+/', $this->skuSearch) ?: [])
            ->map(fn (string $sku): string => mb_strtoupper(trim($sku)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** @return list<string> */
    private function pageProductSlugs(): array
    {
        return ['decal', 'suncatcher', 'ornament', 'ornament-etsy', 'ornament-amazon-2', 'sticker', 'glass', 'ceramic'];
    }

    /** @param list<string> $skus */
    private function resultsQuery(array $skus, bool $filterUser = true): Builder
    {
        $query = ProductDesignAsset::query()
            ->with(['user:id,name,email', 'product:id,name,slug'])
            ->whereIn('sku', $skus);

        if ($filterUser && in_array($this->filterMode, ['user', 'user_product'], true) && $this->userId) {
            $query->where('user_id', $this->userId);
        }

        if (in_array($this->filterMode, ['product', 'user_product'], true) && $this->productId) {
            $query->where('product_id', $this->productId);
        }

        return $query->orderBy('sku')->orderBy('id');
    }
}
