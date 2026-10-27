<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEvaluationRequest;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationRule;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(User $target): Response|RedirectResponse
    {
        abort_if(! $target->active, 404);

        if (! $this->canEvaluate(auth()->user(), $target)) {
            return to_route('targets.index')->withErrors(['evaluation' => 'Anda tidak memiliki kewenangan untuk menilai target ini.']);
        }

        $period = EvaluationPeriod::active()->first();
        $template = $target->evaluationTemplate()->where('active', true)->with('activeQuestions')->first();

        if (! $period || ! $template) {
            return to_route('targets.index')->withErrors(['evaluation' => 'Periode atau template evaluasi belum aktif.']);
        }

        if (Evaluation::query()->where([
            'evaluator_id' => auth()->id(),
            'target_id' => $target->id,
            'evaluation_period_id' => $period->id,
        ])->exists()) {
            return to_route('targets.index')->withErrors(['evaluation' => 'Evaluasi untuk target ini sudah dikirim.']);
        }

        return Inertia::render('Evaluations/Create', [
            'target' => $target->only(['id', 'name', 'avatar_url', 'role']),
            'period' => $period->only(['id', 'month', 'year']),
            'template' => $template->only(['id', 'target_category', 'description']),
            'questions' => $template->activeQuestions->map(fn ($question) => $question->only(['id', 'question_number', 'question_text']))->values(),
        ]);
    }

    public function store(StoreEvaluationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $target = User::query()
            ->with(['position', 'departments', 'factories', 'evaluationTemplate'])
            ->whereKey($data['target_id'])
            ->where('active', true)
            ->firstOrFail();
        abort_if($target->id === $request->user()->id, 422, 'Tidak dapat menilai diri sendiri.');
        abort_unless($this->canEvaluate($request->user(), $target), 403, 'Anda tidak memiliki kewenangan untuk menilai target ini.');

        $period = EvaluationPeriod::active()->firstOrFail();
        $template = $target->evaluationTemplate()->whereKey($data['template_id'])->where('active', true)->with('activeQuestions')->firstOrFail();
        $questionIds = $template->activeQuestions->pluck('id')->sort()->values();
        $submittedIds = collect(array_keys($data['scores']))->map(fn ($id) => (int) $id)->sort()->values();
        abort_unless($questionIds->all() === $submittedIds->all(), 422, 'Jawaban evaluasi tidak lengkap.');

        try {
            $evaluation = DB::transaction(function () use ($request, $data, $target, $period, $template): Evaluation {
                $scores = collect($data['scores']);
                $evaluation = Evaluation::create([
                    'evaluator_id' => $request->user()->id,
                    'target_id' => $target->id,
                    'evaluation_template_id' => $template->id,
                    'evaluation_period_id' => $period->id,
                    'target_category' => $template->target_category,
                    'average_score' => $scores->avg(),
                ]);

                $evaluation->details()->createMany($scores->map(fn ($score, $questionId) => [
                    'question_id' => $questionId,
                    'score' => $score,
                ])->values()->all());

                return $evaluation;
            });
        } catch (UniqueConstraintViolationException) {
            return to_route('targets.index')->withErrors(['evaluation' => 'Evaluasi untuk target ini sudah dikirim.']);
        }

        $this->audit->record('evaluation.submitted', $evaluation, [
            'target_id' => $target->id,
            'period_id' => $period->id,
            'question_count' => count($data['scores']),
        ]);

        return to_route('dashboard')->with('success', 'Evaluasi berhasil dikirim.');
    }

    private function canEvaluate(User $evaluator, User $target): bool
    {
        $evaluator->loadMissing(['position', 'departments', 'factories']);
        $target->loadMissing(['position', 'departments', 'factories', 'evaluationTemplate']);

        if (! $target->evaluationTemplate) {
            return false;
        }

        // Legacy/unassigned targets have no position rule to evaluate yet.
        // Configured targets are always checked against evaluation_rules.
        if (! $target->position) {
            return true;
        }

        if (! $evaluator->position) {
            return false;
        }

        $targetCategory = strtolower((string) $target->evaluationTemplate->target_category);
        $rule = EvaluationRule::query()
            ->where('evaluator_position_id', $evaluator->position->id)
            ->where('active', true)
            ->when($targetCategory === 'hrd', fn ($query) => $query->where('target_category', 'hrd')->whereNull('target_position_id'))
            ->when($targetCategory !== 'hrd', fn ($query) => $query->whereNull('target_category')->where('target_position_id', $target->position?->id))
            ->first();

        if (! $rule) {
            return false;
        }

        if ($rule->same_department && ! $evaluator->departments->pluck('id')->intersect($target->departments->pluck('id'))->isNotEmpty()) {
            return false;
        }

        return ! $rule->same_factory || $evaluator->factories->pluck('id')->intersect($target->factories->pluck('id'))->isNotEmpty();
    }
}
