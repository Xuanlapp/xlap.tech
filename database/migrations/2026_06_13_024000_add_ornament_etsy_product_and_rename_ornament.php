<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add the Ornament Etsy page and rename the current Ornament page for Amazon.
     */
    public function up(): void
    {
        $now = now();

        DB::table('products')->updateOrInsert(
            ['slug' => 'ornament'],
            [
                'name' => 'Ornament Amazon',
                'description' => 'Create Amazon ornament-ready artwork.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        DB::table('products')->updateOrInsert(
            ['slug' => 'ornament-etsy'],
            [
                'name' => 'Ornament Etsy',
                'description' => 'Create Etsy ornament-ready artwork.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        $productId = DB::table('products')->where('slug', 'ornament-etsy')->value('id');

        if (! $productId) {
            return;
        }

        $userIds = DB::table('users')
            ->where('is_admin', true)
            ->pluck('id');

        if ($userIds->isEmpty()) {
            $firstUserId = DB::table('users')->orderBy('id')->value('id');
            $userIds = $firstUserId ? collect([$firstUserId]) : collect();
        }

        $userIds->each(function (int $userId) use ($productId, $now): void {
            DB::table('product_user')->insertOrIgnore([
                'user_id' => $userId,
                'product_id' => $productId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    /**
     * Keep content and user access rows intact, only hide the Etsy product on rollback.
     */
    public function down(): void
    {
        DB::table('products')
            ->where('slug', 'ornament')
            ->update([
                'name' => 'Ornament',
                'description' => 'Create ornament-ready artwork.',
                'updated_at' => now(),
            ]);

        DB::table('products')
            ->where('slug', 'ornament-etsy')
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }
};
