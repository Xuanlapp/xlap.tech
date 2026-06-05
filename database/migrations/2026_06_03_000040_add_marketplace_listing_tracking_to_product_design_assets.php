<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table): void {
            $table->string('marketplace_listing_status')->nullable()->after('tags');
            $table->string('marketplace_listing_marketplace')->nullable()->after('marketplace_listing_status');
            $table->unsignedInteger('marketplace_listing_attempts')->default(0)->after('marketplace_listing_marketplace');
            $table->timestamp('marketplace_listing_started_at')->nullable()->after('marketplace_listing_attempts');
            $table->timestamp('marketplace_listing_completed_at')->nullable()->after('marketplace_listing_started_at');
            $table->text('marketplace_listing_error')->nullable()->after('marketplace_listing_completed_at');
            $table->index(['is_approved', 'marketplace_listing_status', 'title'], 'pda_listing_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table): void {
            $table->dropIndex('pda_listing_status_idx');
            $table->dropColumn([
                'marketplace_listing_status',
                'marketplace_listing_marketplace',
                'marketplace_listing_attempts',
                'marketplace_listing_started_at',
                'marketplace_listing_completed_at',
                'marketplace_listing_error',
            ]);
        });
    }
};
