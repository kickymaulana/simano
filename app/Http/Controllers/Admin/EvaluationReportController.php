<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EvaluationReportRequest;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\Factory;
use App\Models\Position;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvaluationReportController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function resetPage(): Response
    {
        return Inertia::render('Admin/Settings/EvaluationReset', [
            'periods' => EvaluationPeriod::query()->orderByDesc('year')->orderByDesc('month')->get(['id', 'month', 'year', 'status']),
            'targets' => User::query()->whereHas('receivedEvaluations')->orderBy('name')->get(['id', 'name', 'nik']),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'scope' => ['required', 'in:all,period,target'],
            'period_id' => ['nullable', 'integer', 'exists:evaluation_periods,id'],
            'target_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        abort_if($data['scope'] === 'period' && empty($data['period_id']), 422, 'Periode wajib dipilih.');
        abort_if($data['scope'] === 'target' && (empty($data['period_id']) || empty($data['target_id'])), 422, 'Periode dan target wajib dipilih.');

        $query = Evaluation::query()
            ->when($data['period_id'] ?? null, fn (Builder $query, int $id) => $query->where('evaluation_period_id', $id))
            ->when($data['target_id'] ?? null, fn (Builder $query, int $id) => $query->where('target_id', $id));
        $count = $query->count();

        DB::transaction(function () use ($query, $data, $count): void {
            $query->delete();
            $this->audit->record('evaluations.reset', null, [
                'scope' => $data['scope'],
                'period_id' => $data['period_id'] ?? null,
                'target_id' => $data['target_id'] ?? null,
                'deleted_count' => $count,
            ]);
        });

        Cache::flush();

        return back()->with('success', "Reset selesai. {$count} evaluasi dan seluruh detailnya dihapus.");
    }

    public function trend(EvaluationReportRequest $request): Response
    {
        $filterKeys = ['target', 'position_id', 'factory_id', 'department_id'];
        $trend = Cache::remember('evaluation-report:trend:'.md5(json_encode($request->only($filterKeys))), now()->addSeconds(60), function () use ($request) {
            $query = Evaluation::query()
                ->selectRaw('target_id, evaluation_period_id, COUNT(*) as evaluation_count, ROUND(AVG(average_score), 2) as average_score')
                ->with(['target:id,name,role', 'period:id,month,year']);

            return $this->applyActiveTemplate($this->applyFilters($query, $request))
                ->groupBy('target_id', 'evaluation_period_id')
                ->get()
                ->sortBy(fn (Evaluation $evaluation) => sprintf('%s-%02d', $evaluation->period->year, $evaluation->period->month))
                ->values()
                ->map(fn (Evaluation $evaluation) => [
                    'period' => $evaluation->period->only(['id', 'month', 'year']),
                    'target' => $evaluation->target->only(['id', 'name', 'role']),
                    'evaluation_count' => (int) $evaluation->evaluation_count,
                    'average_score' => (float) $evaluation->average_score,
                ]);
        });

        return Inertia::render('Admin/Reports/Trend', [
            ...$this->filterOptions(),
            'filters' => $this->selectedFilters($request),
            'trend' => $trend,
        ]);
    }

    public function exportPdf(EvaluationReportRequest $request): HttpResponse
    {
        $period = $this->period($request);
        $rows = $this->aggregate($request, $period);

        $this->auditExport('evaluation_report.pdf_exported', $request, $period, $rows->count());

        return Pdf::loadView('exports.evaluation-report', compact('period', 'rows'))
            ->download('simano-rekap-evaluasi.pdf');
    }

    public function export(EvaluationReportRequest $request): StreamedResponse
    {
        $period = $this->period($request);
        $rows = $this->aggregate($request, $period);

        $this->auditExport('evaluation_report.exported', $request, $period, $rows->count());

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Periode', 'Target', 'NIK', 'Jabatan', 'Departemen', 'Pabrik', 'Jumlah Evaluasi', 'Skor 5', 'Skor 4', 'Skor 3', 'Skor 2', 'Skor 1']);

            foreach ($rows as $row) {
                $scorePercentages = $row->score_percentages;

                fputcsv($handle, [
                    sprintf('%02d/%d', $row->period->month, $row->period->year),
                    $row->target->name,
                    $row->target->nik,
                    $row->target->position?->name ?? '-',
                    $row->target->departments->pluck('name')->join(', ') ?: '-',
                    $row->target->factories->pluck('name')->join(', ') ?: '-',
                    (int) $row->evaluation_count,
                    $scorePercentages[5].'%',
                    $scorePercentages[4].'%',
                    $scorePercentages[3].'%',
                    $scorePercentages[2].'%',
                    $scorePercentages[1].'%',
                ]);
            }

            fclose($handle);
        }, 'simano-rekap-evaluasi.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function index(EvaluationReportRequest $request): Response
    {
        $period = $this->period($request);
        $rows = $this->aggregateQuery($request, $period)
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        $rows->through(function (Evaluation $evaluation): array {
            return [
                'target' => [
                    ...$evaluation->target->only(['id', 'name', 'nik', 'avatar_url', 'role']),
                    'position' => $evaluation->target->position?->only(['id', 'name']),
                    'departments' => $evaluation->target->departments->map->only(['id', 'name'])->values(),
                    'factories' => $evaluation->target->factories->map->only(['id', 'name'])->values(),
                ],
                'evaluation_count' => (int) $evaluation->evaluation_count,
                'score_percentages' => $this->scorePercentages($evaluation),
            ];
        });

        return Inertia::render('Admin/Reports/Index', [
            'periods' => EvaluationPeriod::query()->orderByDesc('year')->orderByDesc('month')->get(['id', 'month', 'year', 'status']),
            'selectedPeriod' => $period?->only(['id', 'month', 'year', 'status']),
            ...$this->filterOptions(),
            'filters' => $this->selectedFilters($request),
            'rows' => $rows,
        ]);
    }

    private function aggregate(EvaluationReportRequest $request, ?EvaluationPeriod $period): Collection
    {
        return $this->aggregateQuery($request, $period)
            ->get()
            ->each(function (Evaluation $evaluation): void {
                $evaluation->score_percentages = $this->scorePercentages($evaluation);
            });
    }

    private function aggregateQuery(EvaluationReportRequest $request, ?EvaluationPeriod $period): Builder
    {
        $detailScores = DB::table('evaluation_details')
            ->selectRaw('evaluation_id, COUNT(*) as response_count')
            ->groupBy('evaluation_id');

        $query = Evaluation::query()
            ->selectRaw('evaluations.target_id, evaluations.evaluation_period_id, MAX(evaluations.evaluation_template_id) as evaluation_template_id, COUNT(*) as evaluation_count, ROUND(AVG(evaluations.average_score), 2) as average_score')
            ->selectRaw('SUM(COALESCE(detail_scores.response_count, 0)) as response_count')
            ->leftJoinSub($detailScores, 'detail_scores', 'detail_scores.evaluation_id', '=', 'evaluations.id')
            ->with(['target.position', 'target.departments', 'target.factories', 'period:id,month,year']);

        return $this->applyActiveTemplate($this->applyFilters($query, $request))
            ->when($period, fn (Builder $query) => $query->where('evaluations.evaluation_period_id', $period->id))
            ->groupBy('evaluations.target_id', 'evaluations.evaluation_period_id')
            ->orderByDesc('average_score');
    }

    private function scorePercentages(Evaluation $evaluation): array
    {
        $questions = DB::table('evaluation_details')
            ->join('evaluations', 'evaluations.id', '=', 'evaluation_details.evaluation_id')
            ->where('evaluations.target_id', $evaluation->target_id)
            ->where('evaluations.evaluation_period_id', $evaluation->evaluation_period_id)
            ->where('evaluations.evaluation_template_id', $evaluation->evaluation_template_id)
            ->selectRaw('question_id, COUNT(*) as response_count, SUM(score = 1) as score_1_count, SUM(score = 2) as score_2_count, SUM(score = 3) as score_3_count, SUM(score = 4) as score_4_count, SUM(score = 5) as score_5_count')
            ->groupBy('question_id')
            ->get();

        if ($questions->isEmpty()) {
            return collect(range(5, 1))->mapWithKeys(fn (int $score) => [$score => 0])->all();
        }

        return collect(range(5, 1))->mapWithKeys(fn (int $score) => [
            $score => round($questions->average(fn ($question): float => round(((int) $question->{'score_'.$score.'_count'} / (int) $question->response_count) * 100))),
        ])->all();
    }

    private function applyActiveTemplate(Builder $query): Builder
    {
        return $query
            ->whereColumn('evaluations.evaluation_template_id', 'users.evaluation_template_id')
            ->join('users', 'users.id', '=', 'evaluations.target_id')
            ->whereHas('target.evaluationTemplate', fn (Builder $template) => $template->where('active', true));
    }

    private function applyFilters(Builder $query, EvaluationReportRequest $request): Builder
    {
        return $query
            ->when($request->filled('target'), fn (Builder $query) => $query->where('evaluations.target_id', $request->integer('target')))
            ->when($request->filled('position_id'), fn (Builder $query) => $query->whereHas('target', fn (Builder $target) => $target->where('position_id', $request->integer('position_id'))))
            ->when($request->filled('factory_id'), fn (Builder $query) => $query->whereHas('target.factories', fn (Builder $factory) => $factory->whereKey($request->integer('factory_id'))))
            ->when($request->filled('department_id'), fn (Builder $query) => $query->whereHas('target.departments', fn (Builder $department) => $department->whereKey($request->integer('department_id'))));
    }

    private function period(EvaluationReportRequest $request): ?EvaluationPeriod
    {
        return EvaluationPeriod::query()
            ->when($request->filled('period'), fn ($query) => $query->whereKey($request->integer('period')))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();
    }

    private function selectedFilters(EvaluationReportRequest $request): array
    {
        return collect(['target', 'position_id', 'factory_id', 'department_id'])
            ->mapWithKeys(fn (string $key) => [$key => $request->filled($key) ? $request->integer($key) : null])
            ->all();
    }

    private function filterOptions(): array
    {
        return [
            'targets' => Cache::remember('evaluation-report:targets', now()->addMinutes(5), fn () => User::query()
                ->whereHas('receivedEvaluations')
                ->with('position:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'nik', 'position_id'])),
            'positions' => Position::query()->orderBy('level')->orderBy('name')->get(['id', 'name']),
            'factories' => Factory::query()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function auditExport(string $event, EvaluationReportRequest $request, ?EvaluationPeriod $period, int $rowCount): void
    {
        $this->audit->record($event, null, [
            'period_id' => $period?->id,
            'target_id' => $request->input('target'),
            'position_id' => $request->input('position_id'),
            'factory_id' => $request->input('factory_id'),
            'department_id' => $request->input('department_id'),
            'row_count' => $rowCount,
        ]);
    }
}
