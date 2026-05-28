<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a simple admin flag for protecting admin-only pages.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('status');
        });

        $firstUser = DB::table('users')->orderBy('id')->first();

        if ($firstUser) {
            DB::table('users')
                ->where('id', $firstUser->id)
                ->update(['is_admin' => true]);
        }
    }

    /**
     * Remove the admin flag.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
