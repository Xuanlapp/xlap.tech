<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('listing_prompt_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('marketplace', 20);
            $table->longText('content');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['product_id', 'marketplace']);
        });

        $service = app(\App\Services\Marketplace\MarketplaceListingMetadataService::class);
        $products = \App\Models\Product::query()->where('is_active', true)->whereNotIn('slug', ['camp', 'proxy'])->get();
        foreach ($products as $product) {
            foreach (['amazon', 'etsy'] as $marketplace) {
                \App\Models\ListingPromptOverride::query()->create([
                    'product_id' => $product->id,
                    'marketplace' => $marketplace,
                    'content' => $service->builtInPromptFor($product, $marketplace),
                ]);
            }
        }
    }
    public function down(): void { Schema::dropIfExists('listing_prompt_overrides'); }
};
