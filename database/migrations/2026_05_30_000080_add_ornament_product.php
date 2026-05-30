<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Register the Ornament product page for existing installations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('products')->updateOrInsert(
            ['slug' => 'ornament'],
            [
                'name' => 'Ornament',
                'description' => 'Create ornament-ready artwork.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        $productId = DB::table('products')->where('slug', 'ornament')->value('id');
        $firstUserId = DB::table('users')->orderBy('id')->value('id');

        if (! $productId || ! $firstUserId) {
            return;
        }

        DB::table('product_user')->insertOrIgnore([
            'user_id' => $firstUserId,
            'product_id' => $productId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Deactivate Ornament without deleting user content.
     */
    public function down(): void
    {
        DB::table('products')
            ->where('slug', 'ornament')
            ->update(['is_active' => false, 'updated_at' => now()]);
    }
};
