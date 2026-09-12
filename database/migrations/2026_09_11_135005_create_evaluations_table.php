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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluator_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('target_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('evaluation_template_id')->constrained()->restrictOnDelete();
            $table->foreignId('evaluation_period_id')->constrained()->restrictOnDelete();
            $table->string('target_category');
            $table->decimal('average_score', 4, 2);
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();

            $table->index(['target_id', 'evaluation_period_id', 'evaluation_template_id'], 'eval_target_period_template_idx');
            $table->index(['target_category', 'evaluation_period_id'], 'eval_category_period_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
