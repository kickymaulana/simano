<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $period = EvaluationPeriod::active()->latest('year')->latest('month')->first();
        $eligibleTargets = User::query()
            ->where('active', true)
            ->where('is_approved', true)
            ->whereHas('evaluationTemplate', fn ($query) => $query->where('active', true));
        $evaluations = Evaluation::query()
            ->join('users', 'users.id', '=', 'evaluations.target_id')
            ->join('evaluation_templates', 'evaluation_templates.id', '=', 'users.evaluation_template_id')
            ->where('users.active', true)
            ->where('users.is_approved', true)
            ->where('evaluation_templates.active', true)
            ->whereColumn('evaluations.evaluation_template_id', 'users.evaluation_template_id')
            ->when($period, fn ($query) => $query->where('evaluations.evaluation_period_id', $period->id), fn ($query) => $query->whereRaw('1 = 0'));
        $evaluatedTargetCount = (clone $evaluations)->distinct()->count('evaluations.target_id');
        $eligibleTargetCount = (clone $eligibleTargets)->count();
        $targetAttentionCount = DB::query()->fromSub(
            (clone $evaluations)
                ->join('evaluation_details', 'evaluation_details.evaluation_id', '=', 'evaluations.id')
                ->select('evaluations.target_id')
                ->groupBy('evaluations.target_id')
                ->havingRaw('SUM(evaluation_details.score IN (4, 5)) * 1.0 / COUNT(*) < 0.8'),
            'target_attention'
        )->count();
        $questionAttentionCount = DB::query()->fromSub(
            (clone $evaluations)
                ->join('evaluation_details', 'evaluation_details.evaluation_id', '=', 'evaluations.id')
                ->select('evaluation_details.question_id')
                ->groupBy('evaluation_details.question_id')
                ->havingRaw('SUM(evaluation_details.score IN (4, 5)) * 1.0 / COUNT(*) < 0.8'),
            'question_attention'
        )->count();

        return Inertia::render('Admin/Dashboard', [
            'period' => $period?->only(['id', 'month', 'year', 'status', 'closes_at']),
            'stats' => [
                'evaluation_count' => (clone $evaluations)->count(),
                'evaluated_target_count' => $evaluatedTargetCount,
                'unevaluated_target_count' => max(0, $eligibleTargetCount - $evaluatedTargetCount),
                'participation_percentage' => $eligibleTargetCount > 0 ? round($evaluatedTargetCount / $eligibleTargetCount * 100, 1) : 0,
                'evaluator_count' => (clone $evaluations)->distinct()->count('evaluations.evaluator_id'),
                'active_user_count' => User::query()->where('active', true)->where('is_approved', true)->count(),
                'average_score' => round((float) ((clone $evaluations)->avg('evaluations.average_score') ?? 0), 2),
                'pending_user_count' => User::query()->whereNotNull('requested_role')->count(),
                'target_attention_count' => $targetAttentionCount,
                'question_attention_count' => $questionAttentionCount,
            ],
        ]);
    }
}
