<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Track when approved asset images were exported to Google Drive.
     */
    public function up(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table): void {
            $table->timestamp('drive_uploaded_at')->nullable()->after('approved_at');
        });
    }

    /**
     * Remove Google Drive export tracking.
     */
    public function down(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table): void {
            $table->dropColumn('drive_uploaded_at');
        });
    }
};
