<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Requests\Admin\EvaluationReportRequest;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationReportController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function trend(EvaluationReportRequest $request): Response
    {
        $trend = Cache::remember('evaluation-report:trend:'.md5(json_encode($request->only(['category', 'target']))), now()->addSeconds(60), fn () => Evaluation::query()
            ->selectRaw('target_id, evaluation_period_id, target_category, COUNT(*) as evaluation_count, ROUND(AVG(average_score), 2) as average_score')
            ->with(['target:id,name,role', 'period:id,month,year'])
            ->when($request->filled('category'), fn ($query) => $query->where('target_category', $request->string('category')->toString()))
            ->when($request->filled('target'), fn ($query) => $query->where('target_id', $request->integer('target')))
            ->groupBy('target_id', 'evaluation_period_id', 'target_category')
            ->get()
            ->sortBy(fn (Evaluation $evaluation) => sprintf('%s-%02d', $evaluation->period->year, $evaluation->period->month))
            ->values()
            ->map(fn (Evaluation $evaluation) => [
                'period' => $evaluation->period->only(['id', 'month', 'year']),
                'target' => $evaluation->target->only(['id', 'name', 'role']),
                'category' => $evaluation->target_category,
                'evaluation_count' => (int) $evaluation->evaluation_count,
                'average_score' => (float) $evaluation->average_score,
            ]));

        return Inertia::render('Admin/Reports/Trend', [
            'categories' => $this->categories(),
            'targets' => $this->targets(),
            'filters' => $request->only(['category', 'target']),
            'trend' => $trend,
        ]);
    }

    public function exportPdf(EvaluationReportRequest $request): HttpResponse
    {
        $period = EvaluationPeriod::query()
            ->when($request->filled('period'), fn ($query) => $query->whereKey($request->integer('period')))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();
        $rows = $this->aggregate($request, $period);

        $this->audit->record('evaluation_report.pdf_exported', null, [
            'period_id' => $period?->id,
            'category' => $request->input('category'),
            'target_id' => $request->input('target'),
            'row_count' => $rows->count(),
        ]);

        return Pdf::loadView('exports.evaluation-report', compact('period', 'rows'))
            ->download('simano-rekap-evaluasi.pdf');
    }

    private function categories()
    {
        return Cache::remember('evaluation-report:categories', now()->addMinutes(5), fn () => Evaluation::query()->distinct()->orderBy('target_category')->pluck('target_category')->values());
    }

    private function targets()
    {
        return Cache::remember('evaluation-report:targets', now()->addMinutes(5), fn () => \App\Models\User::query()->where('active', true)->orderBy('name')->get(['id', 'name']));
    }

    private function aggregate(EvaluationReportRequest $request, ?EvaluationPeriod $period)
    {
        return Evaluation::query()
            ->selectRaw('target_id, evaluation_period_id, target_category, COUNT(*) as evaluation_count, ROUND(AVG(average_score), 2) as average_score')
            ->with(['target:id,name,role', 'period:id,month,year'])
            ->when($period, fn ($query) => $query->where('evaluation_period_id', $period->id))
            ->when($request->filled('category'), fn ($query) => $query->where('target_category', $request->string('category')->toString()))
            ->when($request->filled('target'), fn ($query) => $query->where('target_id', $request->integer('target')))
            ->groupBy('target_id', 'evaluation_period_id', 'target_category')
            ->orderByDesc('average_score')
            ->get();
    }

    public function export(EvaluationReportRequest $request): HttpResponse
    {
        $period = EvaluationPeriod::query()
            ->when($request->filled('period'), fn ($query) => $query->whereKey($request->integer('period')))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        $rows = Evaluation::query()
            ->selectRaw('target_id, evaluation_period_id, target_category, COUNT(*) as evaluation_count, ROUND(AVG(average_score), 2) as average_score')
            ->with(['target:id,name,role', 'period:id,month,year'])
            ->when($period, fn ($query) => $query->where('evaluation_period_id', $period->id))
            ->when($request->filled('category'), fn ($query) => $query->where('target_category', $request->string('category')->toString()))
            ->when($request->filled('target'), fn ($query) => $query->where('target_id', $request->integer('target')))
            ->groupBy('target_id', 'evaluation_period_id', 'target_category')
            ->orderByDesc('average_score')
            ->get();

        $this->audit->record('evaluation_report.exported', null, [
            'period_id' => $period?->id,
            'category' => $request->input('category'),
            'target_id' => $request->input('target'),
            'row_count' => $rows->count(),
        ]);

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Periode', 'Target', 'Kategori', 'Jumlah Evaluasi', 'Rata-rata']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    sprintf('%02d/%d', $row->period->month, $row->period->year),
                    $row->target->name,
                    $row->target_category,
                    (int) $row->evaluation_count,
                    number_format((float) $row->average_score, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, 'simano-rekap-evaluasi.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function index(EvaluationReportRequest $request): Response
    {
        $period = EvaluationPeriod::query()
            ->when($request->filled('period'), fn ($query) => $query->whereKey($request->integer('period')))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        $rows = Evaluation::query()
            ->selectRaw('target_id, evaluation_period_id, target_category, COUNT(*) as evaluation_count, ROUND(AVG(average_score), 2) as average_score')
            ->with('target:id,name,avatar_url,role')
            ->when($period, fn ($query) => $query->where('evaluation_period_id', $period->id))
            ->when($request->filled('category'), fn ($query) => $query->where('target_category', $request->string('category')->toString()))
            ->when($request->filled('target'), fn ($query) => $query->where('target_id', $request->integer('target')))
            ->groupBy('target_id', 'evaluation_period_id', 'target_category')
            ->orderByDesc('average_score')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        $rows->through(fn (Evaluation $evaluation) => [
                'target' => $evaluation->target->only(['id', 'name', 'avatar_url', 'role']),
                'category' => $evaluation->target_category,
                'evaluation_count' => (int) $evaluation->evaluation_count,
                'average_score' => (float) $evaluation->average_score,
            ]);

        return Inertia::render('Admin/Reports/Index', [
            'periods' => EvaluationPeriod::query()->orderByDesc('year')->orderByDesc('month')->get(['id', 'month', 'year', 'status']),
            'selectedPeriod' => $period?->only(['id', 'month', 'year', 'status']),
            'categories' => $this->categories(),
            'targets' => $this->targets(),
            'rows' => $rows,
        ]);
    }
}
