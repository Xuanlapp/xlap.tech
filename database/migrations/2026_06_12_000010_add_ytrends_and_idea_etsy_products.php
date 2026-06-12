<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        foreach ([
            [
                'name' => 'YTrends',
                'slug' => 'ytrends',
                'description' => 'Research product and keyword trends.',
            ],
            [
                'name' => 'Idea Etsy',
                'slug' => 'idea-etsy',
                'description' => 'Research and approve Etsy product ideas.',
            ],
        ] as $product) {
            DB::table('products')->updateOrInsert(
                ['slug' => $product['slug']],
                [
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('products')
            ->whereIn('slug', ['ytrends', 'idea-etsy'])
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }
};
