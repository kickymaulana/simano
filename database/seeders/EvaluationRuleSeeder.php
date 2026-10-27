<?php

namespace Database\Seeders;

use App\Models\EvaluationRule;
use App\Models\Position;
use Illuminate\Database\Seeder;

class EvaluationRuleSeeder extends Seeder
{
    public function run(): void
    {
        EvaluationRule::query()->update(['active' => false]);

        $positionRules = [
            ['OPERATOR', 'LEADER'],
            ['OPERATOR', 'SUPERVISOR'],
            ['LEADER', 'SUPERVISOR'],
            ['LEADER', 'MANAGER'],
            ['SUPERVISOR', 'GM/FM'],
            ['SUPERVISOR', 'FM'],
            ['MANAGER', 'GM/FM'],
            ['MANAGER', 'FM'],
        ];

        foreach ($positionRules as [$evaluator, $target]) {
            $evaluatorPosition = Position::query()->where('name', $evaluator)->first();
            $targetPosition = Position::query()->where('name', $target)->first();

            if (! $evaluatorPosition || ! $targetPosition) {
                continue;
            }

            EvaluationRule::updateOrCreate(
                [
                    'evaluator_position_id' => $evaluatorPosition->id,
                    'target_position_id' => $targetPosition->id,
                    'target_category' => null,
                ],
                [
                    'same_department' => true,
                    'same_factory' => true,
                    'active' => true,
                ],
            );
        }

        foreach (['OPERATOR', 'LEADER', 'SUPERVISOR', 'MANAGER'] as $evaluator) {
            $evaluatorPosition = Position::query()->where('name', $evaluator)->first();

            if (! $evaluatorPosition) {
                continue;
            }

            EvaluationRule::updateOrCreate(
                [
                    'evaluator_position_id' => $evaluatorPosition->id,
                    'target_position_id' => null,
                    'target_category' => 'hrd',
                ],
                [
                    'same_department' => false,
                    'same_factory' => false,
                    'active' => true,
                ],
            );
        }
    }
}
