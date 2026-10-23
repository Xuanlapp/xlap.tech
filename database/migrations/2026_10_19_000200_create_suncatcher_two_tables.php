<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('data_suncatcher_two', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_design_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('product_slug')->default('suncatcher-two')->index();
            $table->string('workflow_name')->nullable(); $table->string('workflow_status')->nullable();
            $table->string('workflow_step_key')->nullable(); $table->string('workflow_step_label')->nullable();
            $table->unsignedInteger('workflow_step_number')->nullable(); $table->unsignedInteger('workflow_total_steps')->nullable();
            $table->string('provider_key')->nullable(); $table->string('text_model')->nullable(); $table->string('image_model')->nullable();
            $table->string('source_platform')->nullable(); $table->text('source_link')->nullable(); $table->text('source_image_link')->nullable(); $table->text('main_image_link')->nullable();
            $table->json('input_data')->nullable(); $table->json('step_data')->nullable(); $table->json('step_errors')->nullable(); $table->text('last_error')->nullable();
            $table->timestamp('started_at')->nullable(); $table->timestamp('workflow_started_at')->nullable(); $table->timestamp('workflow_paused_at')->nullable(); $table->timestamp('workflow_completed_at')->nullable();
            $table->string('status')->nullable(); $table->string('current_step')->nullable(); $table->unsignedInteger('current_step_number')->nullable(); $table->json('steps')->nullable(); $table->json('payload')->nullable(); $table->text('error_message')->nullable(); $table->timestamp('paused_at')->nullable(); $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('suncatcher_two_workflows', function (Blueprint $table): void {
            $table->id(); $table->foreignId('product_design_asset_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider_key')->nullable(); $table->string('text_model')->nullable(); $table->string('image_model')->nullable(); $table->json('workflow_data')->nullable();
            $table->timestamp('script_generated_at')->nullable(); $table->timestamp('prompts_generated_at')->nullable(); $table->timestamp('gallery_saved_at')->nullable(); $table->timestamp('flow_sent_at')->nullable(); $table->timestamps();
        });
        if (! Schema::hasTable('products')) return;
        DB::table('products')->updateOrInsert(['slug' => 'suncatcher-two'], ['name' => 'Suncatcher 2', 'description' => 'Create Suncatcher 2 artwork.', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    }
    public function down(): void { Schema::dropIfExists('suncatcher_two_workflows'); Schema::dropIfExists('data_suncatcher_two'); }
};

