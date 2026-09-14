<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EvaluationReportRequest;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationScorecardController extends Controller
{
    public function __invoke(EvaluationReportRequest $request): Response
    {
        $period = EvaluationPeriod::query()
            ->when($request->filled('period'), fn (Builder $query) => $query->whereKey($request->integer('period')))
            ->orderByDesc('year')->orderByDesc('month')->first();
        $groupBy = $request->input('group_by', 'department');

        $rows = match ($groupBy) {
            'position' => $this->byPosition($period),
            'factory' => $this->byPivot($period, 'factories', 'factory_user', 'factory_id'),
            default => $this->byPivot($period, 'departments', 'department_user', 'department_id'),
        };

        return Inertia::render('Admin/OrganizationScorecard/Index', [
            'periods' => EvaluationPeriod::query()->orderByDesc('year')->orderByDesc('month')->get(['id', 'month', 'year', 'status']),
            'selectedPeriod' => $period?->only(['id', 'month', 'year', 'status']),
            'groupBy' => $groupBy,
            'rows' => $rows,
        ]);
    }

    private function byPosition(?EvaluationPeriod $period)
    {
        return Evaluation::query()
            ->join('users', 'users.id', '=', 'evaluations.target_id')
            ->join('positions', 'positions.id', '=', 'users.position_id')
            ->when($period, fn ($query) => $query->where('evaluations.evaluation_period_id', $period->id))
            ->selectRaw('positions.id, positions.name, COUNT(DISTINCT evaluations.target_id) as employee_count, COUNT(evaluations.id) as evaluation_count, ROUND(AVG(evaluations.average_score), 2) as average_score, MIN(evaluations.average_score) as minimum_score, MAX(evaluations.average_score) as maximum_score')
            ->groupBy('positions.id', 'positions.name')->orderByDesc('average_score')->paginate(10)->withQueryString();
    }

    private function byPivot(?EvaluationPeriod $period, string $table, string $pivot, string $foreignKey)
    {
        return DB::table('evaluations')
            ->join($pivot, "$pivot.user_id", '=', 'evaluations.target_id')
            ->join($table, "$table.id", '=', "$pivot.$foreignKey")
            ->when($period, fn ($query) => $query->where('evaluations.evaluation_period_id', $period->id))
            ->selectRaw("$table.id, $table.name, COUNT(DISTINCT evaluations.target_id) as employee_count, COUNT(evaluations.id) as evaluation_count, ROUND(AVG(evaluations.average_score), 2) as average_score, MIN(evaluations.average_score) as minimum_score, MAX(evaluations.average_score) as maximum_score")
            ->groupBy("$table.id", "$table.name")->orderByDesc('average_score')->paginate(10)->withQueryString();
    }
}
