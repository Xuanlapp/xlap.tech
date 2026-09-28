<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $productId = DB::table('products')->where('slug', 'decal')->value('id');

        if (! $productId) {
            $productId = DB::table('products')->insertGetId([
                'name' => 'Decal',
                'slug' => 'decal',
                'description' => 'Create decal-ready artwork.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $stickerProductId = DB::table('products')->where('slug', 'sticker')->value('id');
        if ($stickerProductId) {
            DB::table('prompts')->where('product_id', $stickerProductId)->orderBy('id')->get()->each(function (object $prompt) use ($productId, $now): void {
                DB::table('prompts')->insertOrIgnore([
                    'user_id' => $prompt->user_id,
                    'product_id' => $productId,
                    'prompt_number' => $prompt->prompt_number,
                    'name' => $prompt->name,
                    'content' => $prompt->content,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
        }

        DB::table('users')
            ->where('is_admin', true)
            ->orWhere('role', 'admin')
            ->orderBy('id')
            ->get(['id'])
            ->each(fn (object $user) => DB::table('product_user')->insertOrIgnore([
                'user_id' => $user->id,
                'product_id' => $productId,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
    }

    public function down(): void
    {
        $productId = DB::table('products')->where('slug', 'decal')->value('id');
        if ($productId) {
            DB::table('product_user')->where('product_id', $productId)->delete();
            DB::table('products')->where('id', $productId)->delete();
        }
    }
};
