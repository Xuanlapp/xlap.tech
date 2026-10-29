<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $productId = DB::table('products')->where('slug', 'order-amazon-paste')->where('is_active', true)->value('id');
        if (! $productId) { return; }
        $now = now();
        DB::table('users')->where('is_admin', false)->whereNotIn('role', ['admin', 'super_admin'])->pluck('id')->each(fn (int $userId) => DB::table('product_user')->insertOrIgnore([
            'user_id' => $userId, 'product_id' => $productId, 'created_at' => $now, 'updated_at' => $now,
        ]));
    }

    public function down(): void
    {
        $productId = DB::table('products')->where('slug', 'order-amazon-paste')->value('id');
        if (! $productId) { return; }
        DB::table('product_user')->where('product_id', $productId)->whereIn('user_id', DB::table('users')->where('is_admin', false)->whereNotIn('role', ['admin', 'super_admin'])->pluck('id'))->delete();
    }
};