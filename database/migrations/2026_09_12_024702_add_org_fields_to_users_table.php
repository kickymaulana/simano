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
            $table->foreignId('position_id')->nullable()->after('role')->constrained('positions')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('position_id')->constrained('departments')->nullOnDelete();
            $table->foreignId('requested_position_id')->nullable()->after('department_id')->constrained('positions')->nullOnDelete();
            $table->foreignId('requested_department_id')->nullable()->after('requested_position_id')->constrained('departments')->nullOnDelete();
        });

        Schema::create('factory_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->primary(['user_id', 'factory_id']);
        });

        Schema::create('requested_factory_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->primary(['user_id', 'factory_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requested_factory_user');
        Schema::dropIfExists('factory_user');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_department_id');
            $table->dropConstrainedForeignId('requested_position_id');
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('position_id');
        });
    }
};
