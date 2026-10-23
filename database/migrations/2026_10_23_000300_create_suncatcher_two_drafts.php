<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('suncatcher_two_drafts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('draft_key');
            $table->unsignedTinyInteger('workflow_step')->default(1);
            $table->string('status')->default('draft')->index();
            $table->json('payload');
            $table->timestamps();
            $table->unique(['user_id', 'draft_key']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suncatcher_two_drafts');
    }
};
