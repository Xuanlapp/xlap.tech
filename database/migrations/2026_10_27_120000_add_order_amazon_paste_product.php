<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('products')->updateOrInsert(
            ['slug' => 'order-amazon-paste'],
            ['name' => 'Order Amazon Paste', 'description' => 'Extract and export pasted Amazon orders.', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        );
        $productId = DB::table('products')->where('slug', 'order-amazon-paste')->value('id');
        DB::table('users')->where('is_admin', true)->pluck('id')->each(fn ($userId) => DB::table('product_user')->insertOrIgnore([
            'user_id' => $userId, 'product_id' => $productId, 'created_at' => $now, 'updated_at' => $now,
        ]));
    }

    public function down(): void
    {
        DB::table('products')->where('slug', 'order-amazon-paste')->update(['is_active' => false, 'updated_at' => now()]);
    }
};
