<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_product', function (Blueprint $table): void {
            $table->id();
            // ID from the external Excel catalog, kept separate from Laravel's primary key.
            $table->unsignedBigInteger('source_id')->unique();
            $table->string('fulfillment_type', 10); // FBM or FBA
            $table->unsignedBigInteger('order_product_id')->index();
            $table->string('product_name', 500);
            $table->timestamps();

            $table->index(['fulfillment_type', 'order_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_product');
    }
};
