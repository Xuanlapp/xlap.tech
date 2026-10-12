<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sku_order_items', function (Blueprint $table): void {
            $table->foreignId('product_id')->nullable()->after('user_id')->constrained('products')->nullOnDelete();
            $table->index(['user_id', 'product_id']);
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('UPDATE sku_order_items SET product_id = (SELECT product_id FROM product_design_assets WHERE product_design_assets.id = sku_order_items.product_design_asset_id) WHERE product_id IS NULL AND product_design_asset_id IS NOT NULL');
        } else {
            DB::statement('UPDATE sku_order_items soi JOIN product_design_assets pda ON pda.id = soi.product_design_asset_id SET soi.product_id = pda.product_id WHERE soi.product_id IS NULL');
        }
    }

    public function down(): void
    {
        Schema::table('sku_order_items', function (Blueprint $table): void {
            $table->dropForeign(['product_id']);
            $table->dropIndex(['user_id', 'product_id']);
            $table->dropColumn('product_id');
        });
    }
};
