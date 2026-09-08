<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sku_order_items', function (Blueprint $table): void {
            $table->string('source', 30)->default('asset_sync')->after('image_link');
            $table->index(['user_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::table('sku_order_items', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'source']);
            $table->dropColumn('source');
        });
    }
};
