<?php

use App\Models\Product;
use App\Services\Marketplace\MarketplaceListingMetadataService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $glassId = Product::query()->where('slug', 'glass')->value('id');
        if (! $glassId) {
            return;
        }

        DB::table('listing_prompt_overrides')
            ->where('product_id', $glassId)
            ->where('marketplace', 'amazon')
            ->where('content', 'like', 'Act as a professional Amazon English content specialist. Create compliant, natural SEO copy for this product: Personalized Christmas Ornaments glass.%')
            ->where('content', 'like', '%customized photo ornaments for christmas; custom christmas ornaments 2026%')
            ->update([
                'content' => app(MarketplaceListingMetadataService::class)->builtInPromptFor(
                    Product::query()->find($glassId), 'amazon',
                ),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
    }
};
