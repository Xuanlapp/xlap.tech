<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store Lifestyle outputs separately from custom PSD mockups.
     */
    public function up(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table) {
            $table->text('lifestyle1')->nullable()->after('redesign');
            $table->text('lifestyle2')->nullable()->after('lifestyle1');
        });
    }

    /**
     * Remove Lifestyle output columns.
     */
    public function down(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table) {
            $table->dropColumn(['lifestyle1', 'lifestyle2']);
        });
    }
};
