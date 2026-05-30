<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add approval metadata used by product workflow filters.
     */
    public function up(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table) {
            $table->boolean('is_approved')->default(false)->after('mockup11');
            $table->timestamp('approved_at')->nullable()->after('is_approved');
            $table->index(['user_id', 'product_id', 'is_approved']);
        });
    }

    /**
     * Remove approval metadata.
     */
    public function down(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'product_id', 'is_approved']);
            $table->dropColumn(['is_approved', 'approved_at']);
        });
    }
};
