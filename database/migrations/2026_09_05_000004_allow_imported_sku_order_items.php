<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sku_order_items', function (Blueprint $table): void {
            $table->dropForeign(['product_design_asset_id']);
            $table->foreignId('product_design_asset_id')->nullable()->change();
            $table->foreign('product_design_asset_id')->references('id')->on('product_design_assets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sku_order_items', function (Blueprint $table): void {
            $table->dropForeign(['product_design_asset_id']);
            $table->foreignId('product_design_asset_id')->nullable(false)->change();
            $table->foreign('product_design_asset_id')->references('id')->on('product_design_assets')->cascadeOnDelete();
        });
    }
};
