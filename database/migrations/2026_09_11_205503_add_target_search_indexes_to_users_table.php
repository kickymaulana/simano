<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['active', 'name'], 'users_active_name_idx');
            $table->index(['active', 'role'], 'users_active_role_idx');
            $table->index('nik', 'users_nik_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_active_name_idx');
            $table->dropIndex('users_active_role_idx');
            $table->dropIndex('users_nik_idx');
        });
    }
};
