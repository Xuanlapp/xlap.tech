<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create audit logs for user, admin, and automated system actions.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_type')->default('system');
            $table->string('event');
            $table->text('description')->nullable();
            $table->nullableMorphs('subject');
            $table->json('properties')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['event', 'occurred_at']);
            $table->index(['actor_type', 'occurred_at']);
        });
    }

    /**
     * Drop audit logs.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
