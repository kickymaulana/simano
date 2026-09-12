<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationStatusController extends Controller
{
    public function index(Request $request): Response
    {
        $period = EvaluationPeriod::query()
            ->when($request->filled('period'), fn ($query) => $query->whereKey($request->integer('period')))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        $evaluations = Evaluation::query()
            ->where('evaluator_id', $request->user()->id)
            ->when($period, fn ($query) => $query->where('evaluation_period_id', $period->id))
            ->with(['target:id,name,avatar_url,role', 'period:id,month,year', 'template:id,target_category'])
            ->latest('submitted_at')
            ->get(['id', 'target_id', 'evaluation_period_id', 'evaluation_template_id', 'target_category', 'submitted_at']);

        return Inertia::render('Evaluations/Status', [
            'periods' => EvaluationPeriod::query()->orderByDesc('year')->orderByDesc('month')->get(['id', 'month', 'year', 'status']),
            'selectedPeriod' => $period?->only(['id', 'month', 'year', 'status']),
            'evaluations' => $evaluations->map(fn (Evaluation $evaluation) => [
                'id' => $evaluation->id,
                'target' => $evaluation->target->only(['name', 'avatar_url', 'role']),
                'category' => $evaluation->target_category,
                'submitted_at' => $evaluation->submitted_at?->toIso8601String(),
            ]),
        ]);
    }
}
