<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\EvaluationDetail;
use App\Models\EvaluationRule;
use App\Models\EvaluationPeriod;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

class AtasanEvaluationReportController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'period' => ['nullable', 'integer', 'exists:evaluation_periods,id'],
            'target' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $period = EvaluationPeriod::query()->when($validated['period'] ?? null, fn (Builder $query, int $id) => $query->whereKey($id))->latest('year')->latest('month')->first();
        $target = User::query()->with(['position', 'departments', 'factories', 'evaluationTemplate'])->find($validated['target'] ?? null);
        $templateId = $target?->evaluationTemplate?->active ? $target->evaluationTemplate->id : null;
        $rows = collect();
        $evaluatorCount = 0;
        $evaluators = collect();
        $eligibleEvaluatorCount = 0;
        $pendingEvaluatorCount = 0;

        if ($period && $target && $templateId) {
            $evaluators = Evaluation::query()
                ->with(['evaluator.position:id,name', 'evaluator.departments:id,name'])
                ->where('evaluation_period_id', $period->id)
                ->where('target_id', $target->id)
                ->when($templateId, fn (Builder $query, int $id) => $query->where('evaluation_template_id', $id))
                ->get()
                ->unique('evaluator_id')
                ->map(fn (Evaluation $evaluation): array => ['id' => $evaluation->evaluator->id, 'name' => $evaluation->evaluator->name, 'nik' => $evaluation->evaluator->nik, 'position' => $evaluation->evaluator->position?->name])
                ->values();
            $evaluatorCount = $evaluators->count();

            $targetCategory = strtolower((string) $target->evaluationTemplate?->target_category);
            $targetPositionId = $target->position?->id;

            if ($targetCategory !== 'hrd' && $targetPositionId) {
                $evaluatorPositionIds = EvaluationRule::query()
                    ->where('target_position_id', $targetPositionId)
                    ->where('active', true)
                    ->pluck('evaluator_position_id');

                $departmentIds = $target->departments->pluck('id');
                $factoryIds = $target->factories->pluck('id');

                $eligibleEvaluators = User::query()
                    ->with(['position:id,name', 'departments:id,name', 'factories:id,name'])
                    ->where('active', true)
                    ->where('is_approved', true)
                    ->where('id', '!=', $target->id)
                    ->whereIn('position_id', $evaluatorPositionIds)
                    ->when($departmentIds->isNotEmpty(), fn (Builder $query) => $query->whereHas('departments', fn (Builder $departmentQuery) => $departmentQuery->whereIn('departments.id', $departmentIds)))
                    ->when($factoryIds->isNotEmpty(), fn (Builder $query) => $query->whereHas('factories', fn (Builder $factoryQuery) => $factoryQuery->whereIn('factories.id', $factoryIds)))
                    ->orderBy('name')
                    ->get();

                $submittedEvaluatorIds = $evaluators->pluck('id')->filter()->unique();
                $evaluatorCount = $eligibleEvaluators->whereIn('id', $submittedEvaluatorIds)->count();
                $eligibleEvaluatorCount = $eligibleEvaluators->count();
                $pendingEvaluatorCount = $eligibleEvaluatorCount - $evaluatorCount;
            }

            $rows = EvaluationDetail::query()
                ->selectRaw('question_id, COUNT(*) as response_count, SUM(score) as total_score, SUM(score = 1) as score_1, SUM(score = 2) as score_2, SUM(score = 3) as score_3, SUM(score = 4) as score_4, SUM(score = 5) as score_5')
                ->with('question:id,question_number,question_text')
                ->whereHas('evaluation', fn (Builder $query) => $query->where('evaluation_period_id', $period->id)->where('target_id', $target->id)->when($templateId, fn (Builder $query, int $id) => $query->where('evaluation_template_id', $id)))
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
            'selectedTarget' => $target ? array_merge($target->only(['id', 'name', 'nik', 'avatar_url']), ['departments' => $target->departments->pluck('name')->join(', ') ?: null, 'factories' => $target->factories->pluck('name')->join(', ') ?: null, 'target_category' => $target->evaluationTemplate?->target_category]) : null,
            'targetPosition' => $target?->position?->only(['id', 'name']),
            'template' => $target?->evaluationTemplate?->only(['id', 'target_category', 'description']),
            'rows' => $rows,
            'evaluatorCount' => $evaluatorCount,
            'eligibleEvaluatorCount' => $eligibleEvaluatorCount,
            'pendingEvaluatorCount' => $pendingEvaluatorCount,
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
        $target = User::query()->with(['position:id,name', 'departments:id,name', 'factories:id,name', 'evaluationTemplate'])->findOrFail($validated['target']);
        $eligibleIds = $this->eligibleEvaluatorIds($target);
        $evaluators = Evaluation::query()
            ->with(['evaluator.position:id,name', 'evaluator.departments:id,name'])
            ->where('evaluation_period_id', $period->id)
            ->where('target_id', $target->id)
            ->where('evaluation_template_id', $target->evaluation_template_id)
            ->when($eligibleIds !== null, fn (Builder $query) => $query->whereIn('evaluator_id', $eligibleIds))
            ->whereIn('id', Evaluation::query()
                ->selectRaw('MAX(id)')
                ->where('evaluation_period_id', $period->id)
                ->where('target_id', $target->id)
                ->where('evaluation_template_id', $target->evaluation_template_id)
                ->groupBy('evaluator_id'))
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Evaluation $evaluation): array => [
                'evaluation_id' => $evaluation->id,
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

    public function exportEvaluators(Request $request): StreamedResponse
    {
        $validated = $request->validate(['period' => ['required', 'integer', 'exists:evaluation_periods,id'], 'target' => ['required', 'integer', 'exists:users,id']]);
        $period = EvaluationPeriod::findOrFail($validated['period']);
        $target = User::query()->with(['position:id,name', 'departments:id,name', 'factories:id,name', 'evaluationTemplate'])->findOrFail($validated['target']);
        $eligibleIds = $this->eligibleEvaluatorIds($target);
        $rows = Evaluation::query()->with(['evaluator.position:id,name', 'evaluator.departments:id,name', 'evaluator.factories:id,name'])
            ->where('evaluation_period_id', $period->id)->where('target_id', $target->id)->where('evaluation_template_id', $target->evaluation_template_id)
            ->when($eligibleIds !== null, fn (Builder $query) => $query->whereIn('evaluator_id', $eligibleIds))->latest('submitted_at')->get();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb'); fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['No.', 'Nama', 'NIK', 'Jabatan', 'Departemen', 'Pabrik', 'Waktu Penilaian']);
            foreach ($rows as $index => $evaluation) { $user = $evaluation->evaluator; fputcsv($handle, [$index + 1, $user->name, $user->nik, $user->position?->name, $user->departments->pluck('name')->join(', '), $user->factories->pluck('name')->join(', '), $evaluation->submitted_at?->format('d/m/Y H:i')]); }
            fclose($handle);
        }, "sudah-menilai-{$target->id}-{$period->id}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pendingEvaluators(Request $request): Response
    {
        $validated = $request->validate([
            'period' => ['required', 'integer', 'exists:evaluation_periods,id'],
            'target' => ['required', 'integer', 'exists:users,id'],
        ]);
        $period = EvaluationPeriod::findOrFail($validated['period']);
        $target = User::query()->with(['position:id,name', 'departments:id,name', 'factories:id,name', 'evaluationTemplate'])->findOrFail($validated['target']);

        if (strtolower((string) $target->evaluationTemplate?->target_category) === 'hrd' || ! $target->position) {
            $pending = collect();
        } else {
            $positionIds = EvaluationRule::query()
                ->where('target_position_id', $target->position->id)
                ->where('active', true)
                ->pluck('evaluator_position_id');
            $departmentIds = $target->departments->pluck('id');
            $factoryIds = $target->factories->pluck('id');
            $submittedIds = Evaluation::query()
                ->where('evaluation_period_id', $period->id)
                ->where('target_id', $target->id)
                ->where('evaluation_template_id', $target->evaluation_template_id)
                ->pluck('evaluator_id');

            $pending = User::query()
                ->with(['position:id,name', 'departments:id,name', 'factories:id,name'])
                ->where('active', true)
                ->where('is_approved', true)
                ->where('id', '!=', $target->id)
                ->whereIn('position_id', $positionIds)
                ->when($departmentIds->isNotEmpty(), fn (Builder $query) => $query->whereHas('departments', fn (Builder $q) => $q->whereIn('departments.id', $departmentIds)))
                ->when($factoryIds->isNotEmpty(), fn (Builder $query) => $query->whereHas('factories', fn (Builder $q) => $q->whereIn('factories.id', $factoryIds)))
                ->whereNotIn('id', $submittedIds)
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString();
        }

        return Inertia::render('Admin/Reports/PendingEvaluators', [
            'period' => $period->only(['id', 'month', 'year']),
            'target' => $target->only(['id', 'name', 'nik']),
            'targetPosition' => $target->position?->only(['id', 'name']),
            'pendingEvaluators' => $pending instanceof \Illuminate\Pagination\LengthAwarePaginator ? $pending->through(fn (User $evaluator): array => [
                'id' => $evaluator->id,
                'name' => $evaluator->name,
                'avatar_url' => $evaluator->avatar_url,
                'nik' => $evaluator->nik,
                'position' => $evaluator->position?->name,
                'departments' => $evaluator->departments->pluck('name')->join(', ') ?: null,
                'factories' => $evaluator->factories->pluck('name')->join(', ') ?: null,
            ]) : ['data' => [], 'total' => 0, 'current_page' => 1, 'per_page' => 20, 'last_page' => 1, 'links' => []],
        ]);
    }

    public function exportPendingEvaluators(Request $request): StreamedResponse
    {
        $validated = $request->validate(['period' => ['required', 'integer', 'exists:evaluation_periods,id'], 'target' => ['required', 'integer', 'exists:users,id']]);
        $period = EvaluationPeriod::findOrFail($validated['period']);
        $target = User::query()->with(['position:id,name', 'departments:id,name', 'factories:id,name', 'evaluationTemplate'])->findOrFail($validated['target']);
        $eligibleIds = $this->eligibleEvaluatorIds($target);
        $submittedIds = Evaluation::query()->where('evaluation_period_id', $period->id)->where('target_id', $target->id)->where('evaluation_template_id', $target->evaluation_template_id)->pluck('evaluator_id');
        $rows = $eligibleIds === null ? collect() : User::query()->with(['position:id,name', 'departments:id,name', 'factories:id,name'])->whereIn('id', $eligibleIds)->whereNotIn('id', $submittedIds)->orderBy('name')->get();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb'); fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['No.', 'Nama', 'NIK', 'Jabatan', 'Departemen', 'Pabrik']);
            foreach ($rows as $index => $user) { fputcsv($handle, [$index + 1, $user->name, $user->nik, $user->position?->name, $user->departments->pluck('name')->join(', '), $user->factories->pluck('name')->join(', ')]); }
            fclose($handle);
        }, "belum-menilai-{$target->id}-{$period->id}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function eligibleEvaluatorIds(User $target): ?\Illuminate\Support\Collection
    {
        if (strtolower((string) $target->evaluationTemplate?->target_category) === 'hrd' || ! $target->position) return null;
        $positionIds = EvaluationRule::query()->where('target_position_id', $target->position->id)->where('active', true)->pluck('evaluator_position_id');
        $departmentIds = $target->departments->pluck('id'); $factoryIds = $target->factories->pluck('id');
        return User::query()->where('active', true)->where('is_approved', true)->where('id', '!=', $target->id)->whereIn('position_id', $positionIds)
            ->when($departmentIds->isNotEmpty(), fn (Builder $query) => $query->whereHas('departments', fn (Builder $q) => $q->whereIn('departments.id', $departmentIds)))
            ->when($factoryIds->isNotEmpty(), fn (Builder $query) => $query->whereHas('factories', fn (Builder $q) => $q->whereIn('factories.id', $factoryIds)))->pluck('id');
    }

    public function destroy(EvaluationPeriod $period, User $target, Evaluation $evaluation): RedirectResponse
    {
        abort_unless($evaluation->evaluation_period_id === $period->id && $evaluation->target_id === $target->id, 404);

        DB::transaction(function () use ($evaluation): void {
            $snapshot = $evaluation->only(['evaluator_id', 'target_id', 'evaluation_period_id', 'evaluation_template_id', 'target_category', 'average_score', 'submitted_at']);
            $snapshot['detail_count'] = $evaluation->details()->count();
            $evaluation->delete();
            $this->audit->record('evaluation.deleted', null, $snapshot);
        });

        return to_route('admin.reports.evaluations.atasan.evaluators', ['period' => $period->id, 'target' => $target->id])->with('success', 'Evaluasi penilai dihapus.');
    }

    public function exportPdf(Request $request): HttpResponse
    {
        $validated = $request->validate([
            'period' => ['required', 'integer', 'exists:evaluation_periods,id'],
            'target' => ['required', 'integer', 'exists:users,id'],
        ]);
        $period = EvaluationPeriod::findOrFail($validated['period']);
        $target = User::query()->with(['position:id,name', 'evaluationTemplate'])->findOrFail($validated['target']);
        $rows = EvaluationDetail::query()
            ->selectRaw('question_id, COUNT(*) as response_count, SUM(score = 1) as score_1, SUM(score = 2) as score_2, SUM(score = 3) as score_3, SUM(score = 4) as score_4, SUM(score = 5) as score_5')
            ->with('question:id,question_number,question_text')
            ->whereHas('evaluation', fn (Builder $query) => $query->where('evaluation_period_id', $period->id)->where('target_id', $target->id)->where('evaluation_template_id', $target->evaluation_template_id))
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
        $evaluatorCount = Evaluation::query()->where('evaluation_period_id', $period->id)->where('target_id', $target->id)->where('evaluation_template_id', $target->evaluation_template_id)->distinct('evaluator_id')->count('evaluator_id');
        $filename = 'Laporan Evaluasi per Atasan - '.($target->nik ?: 'tanpa-nik').'.pdf';

        return Pdf::loadView('exports.atasan-evaluation-report', compact('period', 'target', 'rows', 'evaluatorCount'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }
}
