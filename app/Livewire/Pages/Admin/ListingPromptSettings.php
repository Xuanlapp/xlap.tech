<?php

namespace App\Livewire\Pages\Admin;

use App\Models\ListingPromptOverride;
use App\Models\Product;
use App\Models\ProductDesignAsset;
use App\Services\Marketplace\MarketplaceListingMetadataService;
use App\Support\ProductRegistry;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ListingPromptSettings extends Component
{
    public int $productId;
    public string $marketplace = 'amazon';
    public string $content = '';
    public array $inputMapping = [];
    public array $outputMapping = [];
    public ?int $sampleAssetId = null;
    public array $sampleData = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $this->productId = Product::query()->whereIn('slug', $this->listingProductSlugs())->where('is_active', true)->orderBy('id')->value('id');
        $this->loadPrompt();
    }

    public function updatedProductId(): void { $this->loadPrompt(); }
    public function updatedMarketplace(): void { $this->loadPrompt(); }

    public function save(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $this->validate([
            'content' => ['required', 'string', 'max:100000'],
            'inputMapping.product' => ['required', 'string', 'in:data_item_add.product -> product.name -> keyword,product.name -> keyword,data_item_add.product'],
            'inputMapping.keyword_phrase' => ['required', 'string', 'in:data_item_add.keyword_phrase -> keyword,keyword,data_item_add.keyword_phrase'],
            'inputMapping.competitor_link' => ['required', 'string', 'in:data_item_add.competitor_link -> data_item_add.product_link -> data_item_add.link,data_item_add.competitor_link,data_item_add.product_link'],
            'outputMapping.*' => ['required', 'string', 'distinct', 'in:title,item_highlight,description,bullet_point_1,bullet_point_2,bullet_point_3,bullet_point_4,bullet_point_5,generic_keyword,tags'],
        ]);
        ListingPromptOverride::updateOrCreate(
            ['product_id' => $this->productId, 'marketplace' => $this->marketplace],
            ['content' => $this->content, 'input_mapping' => $this->inputMapping, 'output_mapping' => $this->outputMapping, 'updated_by' => auth()->id()],
        );
        $this->dispatch('toast', type: 'success', title: 'Da luu', message: 'Da luu prompt Listing metadata vao database.');
    }

    public function resetDefault(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        ListingPromptOverride::query()->where('product_id', $this->productId)->where('marketplace', $this->marketplace)->delete();
        $this->loadPrompt();
        $this->dispatch('toast', type: 'success', title: 'Da reset', message: 'Da dung lai prompt mac dinh.');
    }

    public function render(): View
    {
        return view('livewire.pages.admin.listing-prompt-settings', [
            'products' => Product::query()->whereIn('slug', $this->listingProductSlugs())->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']),
            'inputOptions' => $this->inputOptions(),
            'outputOptions' => $this->outputOptions(),
        ])->layout('layouts.app');
    }

    private function loadPrompt(): void
    {
        $product = Product::find($this->productId);
        $content = ListingPromptOverride::query()->where('product_id', $this->productId)->where('marketplace', $this->marketplace)->value('content')
            ?: app(MarketplaceListingMetadataService::class)->builtInPromptFor($product, $this->marketplace);
        $override = ListingPromptOverride::query()->where('product_id', $this->productId)->where('marketplace', $this->marketplace)->first();
        $this->content = $this->hideSystemSchema((string) $content);
        $this->inputMapping = $override?->input_mapping ?: $this->defaultInputMapping();
        $this->outputMapping = $override?->output_mapping ?: $this->defaultOutputMapping();
        $this->loadSampleData();
    }

    private function hideSystemSchema(string $content): string
    {
        $content = preg_replace('/REQUIRED OUTPUT CONTRACT.*?Do not return Markdown fences or explanatory text\.\s*/is', '', $content) ?? $content;
        $content = preg_replace('/\{\s*"title"\s*:\s*"string".*?"generic_keyword"\s*:\s*"string"\s*\}/is', '', $content) ?? $content;
        $content = preg_replace('/^.*Return ONLY valid JSON with exactly these keys:.*$/im', '', $content) ?? $content;
        $content = preg_replace('/^.*\{(amazon_product_from_sheet|competitor_link|keyword_phrase|product)\}.*$/im', '', $content) ?? $content;

        return trim($content);
    }

    public function updatedSampleAssetId(): void
    {
        $this->loadSampleData();
    }

    private function loadSampleData(): void
    {
        $asset = ProductDesignAsset::query()->where('product_id', $this->productId)->when($this->sampleAssetId, fn ($q) => $q->whereKey($this->sampleAssetId))->latest('id')->first();
        $this->sampleAssetId = $asset?->id;
        $this->sampleData = $asset ? [
            'sku' => $asset->sku,
            'product' => data_get($asset->data_item_add, 'product', $asset->product?->name),
            'keyword' => data_get($asset->data_item_add, 'keyword_phrase', $asset->keyword),
            'competitor_link' => data_get($asset->data_item_add, 'competitor_link', data_get($asset->data_item_add, 'product_link', 'N/A')),
            'item_number' => $asset->item_number,
        ] : [];
    }

    private function defaultInputMapping(): array
    {
        return ['product' => 'data_item_add.product -> product.name -> keyword', 'keyword_phrase' => 'data_item_add.keyword_phrase -> keyword', 'competitor_link' => 'data_item_add.competitor_link -> data_item_add.product_link -> data_item_add.link'];
    }

    private function defaultOutputMapping(): array
    {
        return ['title' => 'title', 'item_highlight' => 'item_highlight', 'description' => 'description', 'bullet_point_1' => 'bullet_point_1', 'bullet_point_2' => 'bullet_point_2', 'bullet_point_3' => 'bullet_point_3', 'bullet_point_4' => 'bullet_point_4', 'bullet_point_5' => 'bullet_point_5', 'generic_keyword' => 'generic_keyword', 'tags' => 'tags'];
    }

    private function inputOptions(): array
    {
        return [
            'product' => ['data_item_add.product -> product.name -> keyword' => 'Dữ liệu sản phẩm (ưu tiên tự động)'],
            'keyword_phrase' => ['data_item_add.keyword_phrase -> keyword' => 'Keyword trong dữ liệu, nếu trống dùng Keyword chính'],
            'competitor_link' => ['data_item_add.competitor_link -> data_item_add.product_link -> data_item_add.link' => 'Link đối thủ (ưu tiên tự động)'],
        ];
    }

    private function outputOptions(): array
    {
        return ['title' => 'Title', 'item_highlight' => 'Item Highlight', 'description' => 'Description', 'bullet_point_1' => 'Bullet Point 1', 'bullet_point_2' => 'Bullet Point 2', 'bullet_point_3' => 'Bullet Point 3', 'bullet_point_4' => 'Bullet Point 4', 'bullet_point_5' => 'Bullet Point 5', 'generic_keyword' => 'Generic Keyword', 'tags' => 'Tags'];
    }

    /** @return array<int, string> */
    private function listingProductSlugs(): array
    {
        return collect(ProductRegistry::all())
            ->reject(fn (array $product): bool => in_array($product['slug'], ['camp', 'proxy', 'ytrends', 'idea-amazon', 'idea-etsy'], true))
            ->pluck('slug')
            ->values()
            ->all();
    }
}

