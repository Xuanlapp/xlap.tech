<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('can_generate_amazon_listing')->default(false)->after('is_admin');
            $table->boolean('can_generate_etsy_listing')->default(false)->after('can_generate_amazon_listing');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'can_generate_amazon_listing',
                'can_generate_etsy_listing',
            ]);
        });
    }
};
