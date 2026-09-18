<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EvaluationReportRequest;
use App\Models\Department;
use App\Models\EvaluationDetail;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationTemplate;
use App\Models\Factory;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class QuestionAnalysisController extends Controller
{
    public function __invoke(EvaluationReportRequest $request): Response
    {
        $period = EvaluationPeriod::query()
            ->when($request->filled('period'), fn (Builder $query) => $query->whereKey($request->integer('period')))
            ->orderByDesc('year')->orderByDesc('month')->first();

        $query = EvaluationDetail::query()
            ->join('evaluations', 'evaluations.id', '=', 'evaluation_details.evaluation_id')
            ->join('users', 'users.id', '=', 'evaluations.target_id')
            ->join('evaluation_templates', function ($join): void {
                $join->on('evaluation_templates.id', '=', 'users.evaluation_template_id')
                    ->where('evaluation_templates.active', true);
            })
            ->selectRaw('evaluation_details.question_id, evaluation_templates.target_category, COUNT(*) as response_count, ROUND(AVG(evaluation_details.score), 2) as average_score')
            ->selectRaw('SUM(evaluation_details.score = 1) as score_1, SUM(evaluation_details.score = 2) as score_2, SUM(evaluation_details.score = 3) as score_3, SUM(evaluation_details.score = 4) as score_4, SUM(evaluation_details.score = 5) as score_5')
            ->with('question:id,question_number,question_text')
            ->whereColumn('evaluations.evaluation_template_id', 'users.evaluation_template_id')
            ->when($period, fn (Builder $query) => $query->where('evaluations.evaluation_period_id', $period->id))
            ->when($request->filled('position_id'), fn (Builder $query) => $query->where('users.position_id', $request->integer('position_id')))
            ->when($request->filled('factory_id'), fn (Builder $query) => $query->whereHas('evaluation.target.factories', fn (Builder $factory) => $factory->whereKey($request->integer('factory_id'))))
            ->when($request->filled('department_id'), fn (Builder $query) => $query->whereHas('evaluation.target.departments', fn (Builder $department) => $department->whereKey($request->integer('department_id'))))
            ->groupBy('evaluation_details.question_id', 'evaluation_templates.target_category')
            ->orderBy('average_score')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/QuestionAnalysis/Index', [
            'periods' => EvaluationPeriod::query()->orderByDesc('year')->orderByDesc('month')->get(['id', 'month', 'year', 'status']),
            'selectedPeriod' => $period?->only(['id', 'month', 'year', 'status']),
            'templateCategories' => EvaluationTemplate::query()->where('active', true)->orderBy('target_category')->pluck('target_category'),
            'positions' => Position::query()->orderBy('level')->orderBy('name')->get(['id', 'name']),
            'factories' => Factory::query()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $this->filters($request),
            'questions' => $query,
        ]);
    }

    private function filters(EvaluationReportRequest $request): array
    {
        return collect(['position_id', 'factory_id', 'department_id'])
            ->mapWithKeys(fn (string $key) => [$key => $request->filled($key) ? $request->integer($key) : null])->all();
    }
}
