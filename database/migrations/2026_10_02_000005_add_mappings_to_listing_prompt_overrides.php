<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('listing_prompt_overrides', function (Blueprint $table): void {
            $table->json('input_mapping')->nullable()->after('content');
            $table->json('output_mapping')->nullable()->after('input_mapping');
        });
    }
    public function down(): void {
        Schema::table('listing_prompt_overrides', function (Blueprint $table): void {
            $table->dropColumn(['input_mapping', 'output_mapping']);
        });
    }
};
