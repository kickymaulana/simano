<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $period = EvaluationPeriod::active()->latest('year')->latest('month')->first();
        $evaluations = Evaluation::query()->when($period, fn ($query) => $query->where('evaluation_period_id', $period->id));
        $targetIds = (clone $evaluations)->select('target_id')->distinct();

        return Inertia::render('Admin/Dashboard', [
            'period' => $period?->only(['id', 'month', 'year', 'status', 'closes_at']),
            'stats' => [
                'evaluation_count' => (clone $evaluations)->count(),
                'evaluated_target_count' => $targetIds->count(),
                'active_user_count' => User::query()->where('active', true)->where('is_approved', true)->count(),
                'average_score' => round((float) ((clone $evaluations)->avg('average_score') ?? 0), 2),
                'pending_user_count' => User::query()->where('is_approved', false)->count(),
            ],
        ]);
    }
}
