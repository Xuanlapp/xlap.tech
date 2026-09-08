<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('history_order_report', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('order_id', 191);
            $table->json('images_link');
            $table->json('report_data')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamps();

            // The same user's order may only be submitted once, even if it is imported again later.
            $table->unique(['user_id', 'order_id'], 'history_order_report_user_order_unique');
            $table->index(['user_id', 'ordered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('history_order_report');
    }
};
