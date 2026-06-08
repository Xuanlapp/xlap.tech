<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store Google Drive upload status and file links per approved product item.
     */
    public function up(): void
    {
        Schema::create('product_drive_uploads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_design_asset_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('waiting');
            $table->json('file_info')->nullable();
            $table->json('drive_files')->nullable();
            $table->string('drive_folder_id')->nullable();
            $table->text('drive_folder_link')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['product_id', 'status']);
        });
    }

    /**
     * Drop Google Drive upload status records.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_drive_uploads');
    }
};
