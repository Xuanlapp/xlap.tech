<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table): void {
            $table->unsignedBigInteger('glass_item_psd_template_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table): void {
            $table->dropColumn('glass_item_psd_template_id');
        });
    }
};
