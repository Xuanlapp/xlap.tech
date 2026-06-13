<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store secondary source images and competitor listing data for product assets.
     */
    public function up(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table): void {
            $table->json('image_sub')->nullable()->after('image_link');
            $table->json('data_item_add')->nullable()->after('image_sub');
        });
    }

    /**
     * Remove secondary source images and competitor listing data.
     */
    public function down(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table): void {
            $table->dropColumn(['image_sub', 'data_item_add']);
        });
    }
};
