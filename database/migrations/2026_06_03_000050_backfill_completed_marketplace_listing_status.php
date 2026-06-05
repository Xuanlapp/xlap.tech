<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('product_design_assets')
            ->where('is_approved', true)
            ->whereNotNull('title')
            ->whereNull('marketplace_listing_status')
            ->update([
                'marketplace_listing_status' => 'completed',
                'marketplace_listing_completed_at' => DB::raw('COALESCE(approved_at, updated_at)'),
            ]);
    }

    public function down(): void
    {
        //
    }
};
