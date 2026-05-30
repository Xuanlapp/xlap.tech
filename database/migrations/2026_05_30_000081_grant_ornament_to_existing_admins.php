<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Grant Ornament access to existing admin users so the new page appears in navigation.
     */
    public function up(): void
    {
        $productId = DB::table('products')->where('slug', 'ornament')->value('id');

        if (! $productId) {
            return;
        }

        $now = now();

        DB::table('users')
            ->where('is_admin', true)
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $userId) use ($productId, $now): void {
                DB::table('product_user')->insertOrIgnore([
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    /**
     * Keep access rows on rollback to avoid deleting user-managed permissions.
     */
    public function down(): void
    {
        //
    }
};
