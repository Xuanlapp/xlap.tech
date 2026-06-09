<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Track when a ready item has been exported to a marketplace CSV.
     */
    public function up(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table): void {
            $table->timestamp('marketplace_exported_at')->nullable()->after('marketplace_listing_error');
            $table->string('marketplace_export_filename')->nullable()->after('marketplace_exported_at');
            $table->index(['is_approved', 'marketplace_exported_at'], 'pda_marketplace_export_idx');
        });
    }

    public function down(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table): void {
            $table->dropIndex('pda_marketplace_export_idx');
            $table->dropColumn(['marketplace_exported_at', 'marketplace_export_filename']);
        });
    }
};
