<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::table('evaluation_rules', function (Blueprint $table): void {
                $table->dropUnique(['evaluator_position_id', 'target_position_id']);
                $table->unsignedBigInteger('target_position_id')->nullable()->change();
                $table->string('target_category', 50)->nullable()->after('target_position_id');
                $table->unique(['evaluator_position_id', 'target_position_id', 'target_category'], 'evaluation_rules_evaluator_target_category_unique');
            });

            return;
        }

        // MariaDB may use the composite unique index to support one or more
        // foreign keys, so remove those foreign keys before replacing the index.
        $foreignKeys = DB::select(<<<'SQL'
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'evaluation_rules'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        SQL);

        foreach ($foreignKeys as $foreignKey) {
            DB::statement('ALTER TABLE evaluation_rules DROP FOREIGN KEY `'.$foreignKey->CONSTRAINT_NAME.'`');
        }

        DB::statement('ALTER TABLE evaluation_rules DROP INDEX evaluation_rules_evaluator_position_id_target_position_id_unique');
        DB::statement('ALTER TABLE evaluation_rules MODIFY target_position_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE evaluation_rules ADD target_category VARCHAR(50) NULL AFTER target_position_id');
        DB::statement('ALTER TABLE evaluation_rules ADD UNIQUE KEY evaluation_rules_evaluator_target_category_unique (evaluator_position_id, target_position_id, target_category)');
        DB::statement('ALTER TABLE evaluation_rules ADD CONSTRAINT evaluation_rules_evaluator_position_id_foreign FOREIGN KEY (evaluator_position_id) REFERENCES positions (id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE evaluation_rules ADD CONSTRAINT evaluation_rules_target_position_id_foreign FOREIGN KEY (target_position_id) REFERENCES positions (id) ON DELETE CASCADE');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::table('evaluation_rules', function (Blueprint $table): void {
                $table->dropUnique('evaluation_rules_evaluator_target_category_unique');
                $table->dropColumn('target_category');
                $table->unsignedBigInteger('target_position_id')->nullable(false)->change();
                $table->unique(['evaluator_position_id', 'target_position_id']);
            });

            return;
        }

        DB::statement('ALTER TABLE evaluation_rules DROP INDEX evaluation_rules_evaluator_target_category_unique');
        DB::statement('ALTER TABLE evaluation_rules DROP COLUMN target_category');
        DB::statement('ALTER TABLE evaluation_rules MODIFY target_position_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE evaluation_rules ADD UNIQUE KEY evaluation_rules_evaluator_position_id_target_position_id_unique (evaluator_position_id, target_position_id)');
    }
};
