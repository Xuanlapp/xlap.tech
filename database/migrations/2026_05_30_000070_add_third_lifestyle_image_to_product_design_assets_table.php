<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the third Lifestyle output slot.
     */
    public function up(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table) {
            $table->text('lifestyle3')->nullable()->after('lifestyle2');
        });
    }

    /**
     * Remove the third Lifestyle output slot.
     */
    public function down(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table) {
            $table->dropColumn('lifestyle3');
        });
    }
};
