<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store user PSD mockup templates and the active template per product feature.
     */
    public function up(): void
    {
        Schema::create('psd_mockup_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('function_key');
            $table->string('name');
            $table->string('original_filename');
            $table->string('storage_path');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'product_id', 'function_key', 'is_active'], 'psd_templates_active_index');
        });
    }

    /**
     * Drop user PSD mockup templates.
     */
    public function down(): void
    {
        Schema::dropIfExists('psd_mockup_templates');
    }
};
