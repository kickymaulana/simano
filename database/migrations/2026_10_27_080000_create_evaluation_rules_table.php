<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evaluator_position_id')->constrained('positions')->cascadeOnDelete();
            $table->foreignId('target_position_id')->constrained('positions')->cascadeOnDelete();
            $table->boolean('same_department')->default(true);
            $table->boolean('same_factory')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['evaluator_position_id', 'target_position_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_rules');
    }
};
