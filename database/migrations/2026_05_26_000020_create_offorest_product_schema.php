<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the simplified Offorest product schema.
     */
    public function up(): void
    {
        $this->dropOldOfforestTables();

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });

        Schema::create('vertex_api_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('project_id')->nullable();
            $table->string('location')->nullable();
            $table->string('client_email')->nullable();
            $table->text('private_key')->nullable();
            $table->json('credentials_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('prompt_number');
            $table->string('name')->nullable();
            $table->longText('content');
            $table->timestamps();

            $table->unique(['user_id', 'product_id', 'prompt_number']);
        });

        Schema::create('product_design_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('item_number');
            $table->string('keyword')->nullable();
            $table->string('image_link', 1000)->nullable();
            $table->text('redesign')->nullable();
            $table->text('mockup1')->nullable();
            $table->text('mockup2')->nullable();
            $table->text('mockup3')->nullable();
            $table->text('mockup4')->nullable();
            $table->text('mockup5')->nullable();
            $table->text('mockup6')->nullable();
            $table->text('mockup7')->nullable();
            $table->text('mockup8')->nullable();
            $table->text('mockup9')->nullable();
            $table->text('mockup10')->nullable();
            $table->text('mockup11')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'product_id']);
            $table->unique(['user_id', 'product_id', 'item_number']);
        });

        $this->seedProducts();
        $this->grantAllProductsToFirstUser();
    }

    /**
     * Drop the simplified Offorest product schema.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_design_assets');
        Schema::dropIfExists('prompts');
        Schema::dropIfExists('vertex_api_credentials');
        Schema::dropIfExists('product_user');
        Schema::dropIfExists('products');
    }

    private function dropOldOfforestTables(): void
    {
        Schema::dropIfExists('user_feature_access');
        Schema::dropIfExists('offorest_features');
        Schema::dropIfExists('vertex_ai_requests');
        Schema::dropIfExists('vertex_ai_credentials');
        Schema::dropIfExists('admin_actions');
        Schema::dropIfExists('ai_job_prompt_revisions');
        Schema::dropIfExists('ai_job_outputs');
        Schema::dropIfExists('ai_job_inputs');
        Schema::dropIfExists('ai_jobs');
        Schema::dropIfExists('service_accounts');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_folders');
        Schema::dropIfExists('prompt_presets');
        Schema::dropIfExists('google_drive_connections');
        Schema::dropIfExists('user_role');
        Schema::dropIfExists('roles');
    }

    private function seedProducts(): void
    {
        $now = now();
        $products = [
            ['name' => 'Redesign', 'slug' => 'redesign', 'description' => 'Create redesign outputs from the source image.'],
            ['name' => 'Mockup', 'slug' => 'mockup', 'description' => 'Create product mockup images.'],
            ['name' => 'Sticker', 'slug' => 'sticker', 'description' => 'Create sticker-ready artwork.'],
            ['name' => 'Ornament', 'slug' => 'ornament', 'description' => 'Create ornament-ready artwork.'],
            ['name' => 'Poster', 'slug' => 'poster', 'description' => 'Create poster-style assets.'],
        ];

        foreach ($products as $product) {
            DB::table('products')->insert([
                ...$product,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function grantAllProductsToFirstUser(): void
    {
        $firstUser = DB::table('users')->orderBy('id')->first();

        if (! $firstUser) {
            return;
        }

        $now = now();
        DB::table('products')->orderBy('id')->get()->each(function (object $product) use ($firstUser, $now): void {
            DB::table('product_user')->insertOrIgnore([
                'user_id' => $firstUser->id,
                'product_id' => $product->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }
};
