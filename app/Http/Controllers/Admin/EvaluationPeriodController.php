<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EvaluationPeriodRequest;
use App\Models\EvaluationPeriod;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationPeriodController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): Response
    {
        return Inertia::render('Admin/EvaluationPeriods/Index', [
            'periods' => EvaluationPeriod::query()
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/EvaluationPeriods/Form', [
            'period' => null,
        ]);
    }

    public function store(EvaluationPeriodRequest $request): RedirectResponse
    {
        $period = DB::transaction(function () use ($request): EvaluationPeriod {
            $data = $request->validated();

            if ($data['status'] === 'active') {
                EvaluationPeriod::query()->where('status', 'active')->update(['status' => 'closed']);
            }

            return EvaluationPeriod::create($data);
        });

        $this->audit->record('evaluation_period.created', $period, [
            'month' => $period->month,
            'year' => $period->year,
            'status' => $period->status,
        ]);

        return to_route('admin.evaluation-periods.index')->with('success', 'Periode evaluasi dibuat.');
    }

    public function edit(EvaluationPeriod $evaluationPeriod): Response
    {
        return Inertia::render('Admin/EvaluationPeriods/Form', [
            'period' => $evaluationPeriod,
        ]);
    }

    public function update(EvaluationPeriodRequest $request, EvaluationPeriod $evaluationPeriod): RedirectResponse
    {
        DB::transaction(function () use ($request, $evaluationPeriod): void {
            $data = $request->validated();

            if ($data['status'] === 'active') {
                EvaluationPeriod::query()
                    ->where('status', 'active')
                    ->where('id', '!=', $evaluationPeriod->id)
                    ->update(['status' => 'closed']);
            }

            $evaluationPeriod->update($data);
        });

        $this->audit->record('evaluation_period.updated', $evaluationPeriod->fresh(), [
            'month' => $evaluationPeriod->month,
            'year' => $evaluationPeriod->year,
            'status' => $evaluationPeriod->status,
        ]);

        return to_route('admin.evaluation-periods.index')->with('success', 'Periode evaluasi diperbarui.');
    }

    public function destroy(EvaluationPeriod $evaluationPeriod): RedirectResponse
    {
        if ($evaluationPeriod->evaluations()->exists()) {
            return to_route('admin.evaluation-periods.index')->withErrors([
                'period' => 'Periode sudah memiliki evaluasi dan tidak dapat dihapus.',
            ]);
        }

        $snapshot = $evaluationPeriod->only(['month', 'year', 'status']);
        $evaluationPeriod->delete();
        $this->audit->record('evaluation_period.deleted', null, $snapshot);

        return to_route('admin.evaluation-periods.index')->with('success', 'Periode evaluasi dihapus.');
    }
}
