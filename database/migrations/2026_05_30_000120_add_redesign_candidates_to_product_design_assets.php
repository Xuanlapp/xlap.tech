<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Keep generated master image candidates so users can select an older output.
     */
    public function up(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table): void {
            $table->json('redesign_candidates')->nullable()->after('redesign');
        });
    }

    /**
     * Remove generated master image candidates.
     */
    public function down(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table): void {
            $table->dropColumn('redesign_candidates');
        });
    }
};
