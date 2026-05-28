<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('product_design_assets', 'item_number')) {
            return;
        }

        Schema::table('product_design_assets', function (Blueprint $table) {
            $table->unsignedInteger('item_number')->nullable()->after('product_id');
        });

        DB::statement(<<<'SQL'
            UPDATE product_design_assets pda
            JOIN (
                SELECT
                    id,
                    ROW_NUMBER() OVER (
                        PARTITION BY user_id, product_id
                        ORDER BY id
                    ) AS seq_num
                FROM product_design_assets
            ) numbered ON numbered.id = pda.id
            SET pda.item_number = numbered.seq_num
        SQL);

        DB::statement('ALTER TABLE product_design_assets MODIFY item_number INT UNSIGNED NOT NULL');

        Schema::table('product_design_assets', function (Blueprint $table) {
            $table->unique(['user_id', 'product_id', 'item_number']);
        });
    }

    public function down(): void
    {
        Schema::table('product_design_assets', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'product_id', 'item_number']);
            $table->dropColumn('item_number');
        });
    }
};
