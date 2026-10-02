<?php

use App\Models\ListingPromptOverride;
use App\Models\Product;
use App\Services\Marketplace\MarketplaceListingMetadataService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $service = app(MarketplaceListingMetadataService::class);
        Product::query()->where('is_active', true)->whereNotIn('slug', ['camp', 'proxy'])->get()->each(function (Product $product) use ($service): void {
            foreach (['amazon', 'etsy'] as $marketplace) {
                ListingPromptOverride::query()->firstOrCreate(
                    ['product_id' => $product->id, 'marketplace' => $marketplace],
                    ['content' => $service->builtInPromptFor($product, $marketplace)],
                );
            }
        });
    }

    public function down(): void
    {
        // Prompt rows are user-editable data; leave them intact on rollback.
    }
};
