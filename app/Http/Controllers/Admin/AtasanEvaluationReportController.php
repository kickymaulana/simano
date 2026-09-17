<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\EvaluationDetail;
use App\Models\EvaluationPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AtasanEvaluationReportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'period' => ['nullable', 'integer', 'exists:evaluation_periods,id'],
            'target' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $period = EvaluationPeriod::query()->when($validated['period'] ?? null, fn (Builder $query, int $id) => $query->whereKey($id))->latest('year')->latest('month')->first();
        $target = User::query()->with(['position', 'departments', 'factories'])->find($validated['target'] ?? null);
        $rows = collect();
        $evaluatorCount = 0;

        if ($period && $target) {
            $evaluatorCount = Evaluation::query()
                ->where('evaluation_period_id', $period->id)
                ->where('target_id', $target->id)
                ->distinct('evaluator_id')
                ->count('evaluator_id');
            $rows = EvaluationDetail::query()
                ->selectRaw('question_id, COUNT(*) as response_count, SUM(score) as total_score, SUM(score = 1) as score_1, SUM(score = 2) as score_2, SUM(score = 3) as score_3, SUM(score = 4) as score_4, SUM(score = 5) as score_5')
                ->with('question:id,question_number,question_text')
                ->whereHas('evaluation', fn (Builder $query) => $query->where('evaluation_period_id', $period->id)->where('target_id', $target->id))
                ->groupBy('question_id')
                ->orderBy('question_id')
                ->get()
                ->map(function (EvaluationDetail $detail): array {
                    $count = (int) $detail->response_count;

                    return [
                        'question_number' => $detail->question->question_number,
                        'question_text' => $detail->question->question_text,
                        'response_count' => $count,
                        'total_score' => (int) $detail->total_score,
                        'percentages' => collect(range(5, 1))->mapWithKeys(fn (int $score) => [$score => $count ? round(((int) $detail->{'score_'.$score} / $count) * 100) : 0])->all(),
                    ];
                });
        }

        return Inertia::render('Admin/Reports/Atasan', [
            'periods' => EvaluationPeriod::query()->orderByDesc('year')->orderByDesc('month')->get(['id', 'month', 'year', 'status']),
            'targets' => User::query()->whereHas('receivedEvaluations')->with('position:id,name')->orderBy('name')->get(['id', 'name', 'position_id']),
            'selectedPeriod' => $period?->only(['id', 'month', 'year', 'status']),
            'selectedTarget' => $target?->only(['id', 'name', 'nik', 'avatar_url']),
            'targetPosition' => $target?->position?->only(['id', 'name']),
            'rows' => $rows,
            'evaluatorCount' => $evaluatorCount,
            'filters' => ['period' => $validated['period'] ?? null, 'target' => $validated['target'] ?? null],
        ]);
    }
}
