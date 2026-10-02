<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table): void {
            if (! Schema::hasColumn('product_design_assets', 'sku_pattern')) {
                $table->string('sku_pattern')->nullable()->after('sku');
            }
            if (! Schema::hasColumn('product_design_assets', 'item_highlight')) {
                $table->string('item_highlight', 125)->nullable()->after('generic_keyword');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table): void {
            if (Schema::hasColumn('product_design_assets', 'item_highlight')) {
                $table->dropColumn('item_highlight');
            }
            if (Schema::hasColumn('product_design_assets', 'sku_pattern')) {
                $table->dropColumn('sku_pattern');
            }
        });
    }
};
