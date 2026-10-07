<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('products')->updateOrInsert(
            ['slug' => 'ceramic'],
            ['name' => 'Ceramic', 'description' => 'Create ceramic-ready artwork.', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        );

        $productId = DB::table('products')->where('slug', 'ceramic')->value('id');
        $userIds = DB::table('users')->where('is_admin', true)->pluck('id');
        if ($userIds->isEmpty()) {
            $firstUserId = DB::table('users')->orderBy('id')->value('id');
            $userIds = $firstUserId ? collect([$firstUserId]) : collect();
        }
        $userIds->each(fn (int $userId) => DB::table('product_user')->insertOrIgnore([
            'user_id' => $userId, 'product_id' => $productId, 'created_at' => $now, 'updated_at' => $now,
        ]));
    }

    public function down(): void
    {
        DB::table('products')->where('slug', 'ceramic')->update(['is_active' => false, 'updated_at' => now()]);
    }
};
