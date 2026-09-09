<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('history_order_report', function (Blueprint $table): void {
            $table->string('sku', 100)->default('')->after('order_id');
            $table->string('quantity', 30)->default('')->after('size');
            $table->dropUnique('history_order_report_user_order_unique');
            $table->unique(['user_id', 'order_id', 'sku', 'size', 'quantity'], 'history_order_report_variant_unique');
        });
    }

    public function down(): void
    {
        Schema::table('history_order_report', function (Blueprint $table): void {
            $table->dropUnique('history_order_report_variant_unique');
            $table->dropColumn(['sku', 'quantity']);
            $table->unique(['user_id', 'order_id'], 'history_order_report_user_order_unique');
        });
    }
};
