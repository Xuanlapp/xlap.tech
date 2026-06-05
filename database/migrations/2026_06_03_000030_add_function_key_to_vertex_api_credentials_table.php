<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE vertex_api_credentials MODIFY user_id BIGINT UNSIGNED NULL');
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE vertex_api_credentials ALTER COLUMN user_id DROP NOT NULL');
        }

        Schema::table('vertex_api_credentials', function (Blueprint $table): void {
            $table->string('function_key')->default('image_generation')->after('user_id');
            $table->index(['function_key', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('vertex_api_credentials', function (Blueprint $table): void {
            $table->dropIndex(['function_key', 'is_active']);
            $table->dropColumn('function_key');
        });

        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE vertex_api_credentials MODIFY user_id BIGINT UNSIGNED NOT NULL');
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE vertex_api_credentials ALTER COLUMN user_id SET NOT NULL');
        }
    }
};
