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
        Schema::table('evaluations', function (Blueprint $table) {
            $table->unique(
                ['evaluator_id', 'target_id', 'evaluation_period_id'],
                'eval_evaluator_target_period_uq',
            );
            $table->index(
                ['target_id', 'evaluation_period_id', 'target_category'],
                'eval_report_target_period_category_idx',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropUnique('eval_evaluator_target_period_uq');
            $table->dropIndex('eval_report_target_period_category_idx');
        });
    }
};
