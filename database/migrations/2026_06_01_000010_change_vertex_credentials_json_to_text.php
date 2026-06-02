<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Encrypted Laravel casts store ciphertext strings, so this column must be text, not JSON.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE vertex_api_credentials MODIFY credentials_json LONGTEXT NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE vertex_api_credentials ALTER COLUMN credentials_json TYPE TEXT USING credentials_json::text');
        }
    }

    /**
     * Keep rollback conservative because ciphertext is not valid JSON.
     */
    public function down(): void
    {
        //
    }
};
