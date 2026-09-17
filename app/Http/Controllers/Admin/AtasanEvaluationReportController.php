<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\EvaluationDetail;
use App\Models\EvaluationPeriod;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
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
        $evaluators = collect();

        if ($period && $target) {
            $evaluators = Evaluation::query()
                ->with(['evaluator.position:id,name', 'evaluator.departments:id,name'])
                ->where('evaluation_period_id', $period->id)
                ->where('target_id', $target->id)
                ->get()
                ->unique('evaluator_id')
                ->map(fn (Evaluation $evaluation): array => ['id' => $evaluation->evaluator->id, 'name' => $evaluation->evaluator->name, 'nik' => $evaluation->evaluator->nik, 'position' => $evaluation->evaluator->position?->name])
                ->values();
            $evaluatorCount = $evaluators->count();
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
            'selectedTarget' => $target ? array_merge($target->only(['id', 'name', 'nik', 'avatar_url']), ['departments' => $target->departments->pluck('name')->join(', ') ?: null]) : null,
            'targetPosition' => $target?->position?->only(['id', 'name']),
            'rows' => $rows,
            'evaluatorCount' => $evaluatorCount,
            'filters' => ['period' => $validated['period'] ?? null, 'target' => $validated['target'] ?? null],
        ]);
    }

    public function evaluators(Request $request): Response
    {
        $validated = $request->validate([
            'period' => ['required', 'integer', 'exists:evaluation_periods,id'],
            'target' => ['required', 'integer', 'exists:users,id'],
        ]);
        $period = EvaluationPeriod::findOrFail($validated['period']);
        $target = User::query()->with('position:id,name')->findOrFail($validated['target']);
        $evaluators = Evaluation::query()
            ->with(['evaluator.position:id,name', 'evaluator.departments:id,name'])
            ->where('evaluation_period_id', $period->id)
            ->where('target_id', $target->id)
            ->latest('submitted_at')
            ->paginate(20)
            ->through(fn (Evaluation $evaluation): array => [
                'id' => $evaluation->evaluator->id,
                'name' => $evaluation->evaluator->name,
                'avatar_url' => $evaluation->evaluator->avatar_url,
                'nik' => $evaluation->evaluator->nik,
                'position' => $evaluation->evaluator->position?->name,
                'departments' => $evaluation->evaluator->departments->pluck('name')->join(', ') ?: null,
                'submitted_at' => $evaluation->submitted_at?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Admin/Reports/Evaluators', [
            'period' => $period->only(['id', 'month', 'year']),
            'target' => $target->only(['id', 'name', 'nik']),
            'targetPosition' => $target->position?->only(['id', 'name']),
            'evaluators' => $evaluators,
        ]);
    }

    public function exportPdf(Request $request): HttpResponse
    {
        $validated = $request->validate([
            'period' => ['required', 'integer', 'exists:evaluation_periods,id'],
            'target' => ['required', 'integer', 'exists:users,id'],
        ]);
        $period = EvaluationPeriod::findOrFail($validated['period']);
        $target = User::query()->with('position:id,name')->findOrFail($validated['target']);
        $rows = EvaluationDetail::query()
            ->selectRaw('question_id, COUNT(*) as response_count, SUM(score = 1) as score_1, SUM(score = 2) as score_2, SUM(score = 3) as score_3, SUM(score = 4) as score_4, SUM(score = 5) as score_5')
            ->with('question:id,question_number,question_text')
            ->whereHas('evaluation', fn (Builder $query) => $query->where('evaluation_period_id', $period->id)->where('target_id', $target->id))
            ->groupBy('question_id')
            ->orderBy('question_id')
            ->get()
            ->map(function (EvaluationDetail $detail): array {
                $count = (int) $detail->response_count;

                return [
                    'number' => $detail->question->question_number,
                    'text' => $detail->question->question_text,
                    'scores' => collect(range(5, 1))->mapWithKeys(fn (int $score) => [$score => $count ? round(((int) $detail->{'score_'.$score} / $count) * 100) : 0])->all(),
                ];
            });
        $evaluatorCount = Evaluation::query()->where('evaluation_period_id', $period->id)->where('target_id', $target->id)->distinct('evaluator_id')->count('evaluator_id');
        $filename = 'Laporan Evaluasi per Atasan - '.($target->nik ?: 'tanpa-nik').'.pdf';

        return Pdf::loadView('exports.atasan-evaluation-report', compact('period', 'target', 'rows', 'evaluatorCount'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }
}
