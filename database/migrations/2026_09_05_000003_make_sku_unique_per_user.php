<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('product_design_assets')
            ->select('user_id', 'sku')
            ->whereNotNull('sku')
            ->where('sku', '!=', '')
            ->groupBy('user_id', 'sku')
            ->havingRaw('COUNT(*) > 1')
            ->limit(10)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $examples = $duplicates
                ->map(fn (object $row): string => "user {$row->user_id}: {$row->sku}")
                ->implode(', ');

            throw new \RuntimeException("Cannot enforce unique SKU per user until duplicate SKUs are renamed. Examples: {$examples}");
        }

        Schema::table('product_design_assets', function (Blueprint $table): void {
            $table->dropUnique('product_design_assets_user_product_sku_unique');
            $table->unique(['user_id', 'sku'], 'product_design_assets_user_sku_unique');
        });
    }

    public function down(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table): void {
            $table->dropUnique('product_design_assets_user_sku_unique');
            $table->unique(['user_id', 'product_id', 'sku'], 'product_design_assets_user_product_sku_unique');
        });
    }
};
