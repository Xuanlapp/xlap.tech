<?php

use App\Models\ListingPromptOverride;
use App\Models\Product;
use App\Services\Marketplace\MarketplaceListingMetadataService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $product = Product::query()->where('slug', 'ceramic')->first();
        if (! $product) {
            return;
        }

        $override = ListingPromptOverride::query()
            ->where('product_id', $product->id)
            ->where('marketplace', 'amazon')
            ->first();
        if (! $override) {
            ListingPromptOverride::query()->create([
                'product_id' => $product->id,
                'marketplace' => 'amazon',
                'content' => app(MarketplaceListingMetadataService::class)->builtInPromptFor($product, 'amazon'),
            ]);
            return;
        }

        $isLegacyCeramicPrompt = str_contains((string) $override->content, 'Act as a professional English Amazon listing writer. Write for the actual glass Christmas ornament');
        if ($isLegacyCeramicPrompt) {
            $override->update([
                'content' => app(MarketplaceListingMetadataService::class)->builtInPromptFor($product, 'amazon'),
            ]);
        }
    }

    public function down(): void
    {
    }
};