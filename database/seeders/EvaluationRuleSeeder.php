<?php

namespace Database\Seeders;

use App\Models\EvaluationRule;
use App\Models\Position;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EvaluationRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            ['OPERATOR', 'LEADER'],
            ['OPERATOR', 'SUPERVISOR'],
            ['OPERATOR', 'MANAGER'],
            ['LEADER', 'SUPERVISOR'],
            ['LEADER', 'MANAGER'],
            ['SUPERVISOR', 'MANAGER'],
        ];

        foreach ($rules as [$evaluator, $target]) {
            $evaluatorPosition = Position::query()->where('name', $evaluator)->first();
            $targetPosition = Position::query()->where('name', $target)->first();

            if (! $evaluatorPosition || ! $targetPosition) {
                continue;
            }

            EvaluationRule::updateOrCreate(
                [
                    'evaluator_position_id' => $evaluatorPosition->id,
                    'target_position_id' => $targetPosition->id,
                ],
                [
                    'same_department' => true,
                    'same_factory' => true,
                    'active' => true,
                ],
            );
        }
    }
}
