<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Offorest profile fields to the base Laravel users table.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            $table->string('avatar_path')->nullable()->after('password');
            $table->string('status')->default('active')->after('avatar_path');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the Offorest profile fields.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['avatar_path', 'status']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
