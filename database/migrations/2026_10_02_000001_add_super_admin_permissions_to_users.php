<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'admin_permissions')) {
                $table->json('admin_permissions')->nullable()->after('role');
            }
        });

        DB::table('users')
            ->where(function ($query): void {
                $query->whereRaw('LOWER(username) = ?', ['adminxlap'])
                    ->orWhereRaw('LOWER(email) = ?', ['adminxlap']);
            })
            ->update(['role' => 'super_admin', 'is_admin' => true]);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'super_admin')->update(['role' => 'admin']);
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'admin_permissions')) {
                $table->dropColumn('admin_permissions');
            }
        });
    }
};
