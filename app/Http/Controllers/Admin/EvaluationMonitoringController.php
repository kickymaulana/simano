<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationRule;
use App\Models\Factory;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvaluationMonitoringController extends Controller
{
    public function rules(): Response
    {
        $rules = EvaluationRule::query()
            ->with(['evaluatorPosition:id,name', 'targetPosition:id,name'])
            ->orderBy('active', 'desc')
            ->orderBy('evaluator_position_id')
            ->orderBy('target_position_id')
            ->get()
            ->map(fn (EvaluationRule $rule): array => [
                'id' => $rule->id,
                'evaluator_position' => $rule->evaluatorPosition?->name,
                'target_position' => $rule->targetPosition?->name,
                'target_category' => $rule->target_category,
                'same_department' => $rule->same_department,
                'same_factory' => $rule->same_factory,
                'active' => $rule->active,
            ]);

        return Inertia::render('Admin/EvaluationRules/Index', ['rules' => $rules]);
    }

    public function pending(Request $request): Response
    {
        $validated = $request->validate([
            'period' => ['nullable', 'integer', 'exists:evaluation_periods,id'],
            'target_position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'evaluator_position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'factory_id' => ['nullable', 'integer', 'exists:factories,id'],
        ]);
        $period = EvaluationPeriod::query()
            ->when($validated['period'] ?? null, fn (Builder $query, int $id) => $query->whereKey($id))
            ->orderByDesc('year')->orderByDesc('month')->first();
        $paginator = $period
            ? $this->pendingQuery($period, $validated)->paginate(20)->withQueryString()
            : new LengthAwarePaginator([], 0, 20, 1, ['path' => $request->url(), 'query' => $request->query()]);

        return Inertia::render('Admin/PendingEvaluations/Index', [
            'periods' => EvaluationPeriod::query()->orderByDesc('year')->orderByDesc('month')->get(['id', 'month', 'year', 'status']),
            'selectedPeriod' => $period?->only(['id', 'month', 'year', 'status']),
            'positions' => Position::query()->orderBy('level')->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'factories' => Factory::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'period' => $validated['period'] ?? null,
                'target_position_id' => $validated['target_position_id'] ?? null,
                'evaluator_position_id' => $validated['evaluator_position_id'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'factory_id' => $validated['factory_id'] ?? null,
            ],
            'rows' => $paginator,
        ]);
    }

    public function exportPending(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'period' => ['required', 'integer', 'exists:evaluation_periods,id'],
            'target_position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'evaluator_position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'factory_id' => ['nullable', 'integer', 'exists:factories,id'],
        ]);
        $period = EvaluationPeriod::findOrFail($validated['period']);
        $rows = $this->pendingQuery($period, $validated)->cursor();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['No.', 'NIK yang Dinilai', 'Nama yang Dinilai', 'Jabatan yang Dinilai', 'NIK Belum Menilai', 'Nama Belum Menilai', 'Jabatan Belum Menilai', 'Departemen Belum Menilai', 'Lokasi/Pabrik Belum Menilai']);
            $index = 1;
            foreach ($rows as $row) {
                fputcsv($handle, [$index++, $row->target_nik, $row->target_name, $row->target_position, $row->evaluator_nik, $row->evaluator_name, $row->evaluator_position, $row->evaluator_departments, $row->evaluator_factories]);
            }
            fclose($handle);
        }, "belum-menilai-{$period->id}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function pendingQuery(EvaluationPeriod $period, array $filters): QueryBuilder
    {
        $query = DB::table('users as targets')
            ->join('evaluation_rules', function ($join): void {
                $join->where('evaluation_rules.active', true);
            })
            ->join('users as evaluators', 'evaluators.position_id', '=', 'evaluation_rules.evaluator_position_id')
            ->leftJoin('evaluation_templates as target_templates', 'target_templates.id', '=', 'targets.evaluation_template_id')
            ->leftJoin('positions as target_positions', 'target_positions.id', '=', 'targets.position_id')
            ->leftJoin('positions as evaluator_positions', 'evaluator_positions.id', '=', 'evaluators.position_id')
            ->where('targets.active', true)
            ->where('targets.is_approved', true)
            ->where('evaluators.active', true)
            ->where('evaluators.is_approved', true)
            ->whereColumn('targets.id', '!=', 'evaluators.id')
            ->where(function (QueryBuilder $query): void {
                $query->where(function (QueryBuilder $query): void {
                    $query->whereNull('evaluation_rules.target_category')
                        ->whereColumn('evaluation_rules.target_position_id', 'targets.position_id');
                })->orWhere(function (QueryBuilder $query): void {
                    $query->where('evaluation_rules.target_category', 'hrd')
                        ->whereNull('evaluation_rules.target_position_id')
                        ->whereRaw("LOWER(target_templates.target_category) = 'hrd'");
                });
            })
            ->where(function (QueryBuilder $query): void {
                $query->where('evaluation_rules.same_department', false)
                    ->orWhereExists(function (QueryBuilder $query): void {
                        $query->selectRaw('1')
                            ->from('department_user as matching_target_departments')
                            ->whereColumn('matching_target_departments.user_id', 'targets.id')
                            ->whereExists(function (QueryBuilder $query): void {
                                $query->selectRaw('1')
                                    ->from('department_user as matching_evaluator_departments')
                                    ->whereColumn('matching_evaluator_departments.user_id', 'evaluators.id')
                                    ->whereColumn('matching_evaluator_departments.department_id', 'matching_target_departments.department_id');
                            });
                    });
            })
            ->where(function (QueryBuilder $query): void {
                $query->where('evaluation_rules.same_factory', false)
                    ->orWhereExists(function (QueryBuilder $query): void {
                        $query->selectRaw('1')
                            ->from('factory_user as matching_target_factories')
                            ->whereColumn('matching_target_factories.user_id', 'targets.id')
                            ->whereExists(function (QueryBuilder $query): void {
                                $query->selectRaw('1')
                                    ->from('factory_user as matching_evaluator_factories')
                                    ->whereColumn('matching_evaluator_factories.user_id', 'evaluators.id')
                                    ->whereColumn('matching_evaluator_factories.factory_id', 'matching_target_factories.factory_id');
                            });
                    });
            })
            ->whereNotExists(function (QueryBuilder $query) use ($period): void {
                $query->selectRaw('1')
                    ->from('evaluations')
                    ->whereColumn('evaluations.evaluator_id', 'evaluators.id')
                    ->whereColumn('evaluations.target_id', 'targets.id')
                    ->where('evaluations.evaluation_period_id', $period->id);
            })
            ->when($filters['target_position_id'] ?? null, fn (QueryBuilder $query, int $id) => $query->where('targets.position_id', $id))
            ->when($filters['evaluator_position_id'] ?? null, fn (QueryBuilder $query, int $id) => $query->where('evaluators.position_id', $id))
            ->when($filters['department_id'] ?? null, fn (QueryBuilder $query, int $id) => $query->whereExists(fn (QueryBuilder $departmentQuery) => $departmentQuery->selectRaw('1')->from('department_user')->whereColumn('department_user.user_id', 'targets.id')->where('department_user.department_id', $id)))
            ->when($filters['factory_id'] ?? null, fn (QueryBuilder $query, int $id) => $query->whereExists(fn (QueryBuilder $factoryQuery) => $factoryQuery->selectRaw('1')->from('factory_user')->whereColumn('factory_user.user_id', 'targets.id')->where('factory_user.factory_id', $id)))
            ->select([
                'targets.nik as target_nik',
                'targets.name as target_name',
                'target_positions.name as target_position',
                'evaluators.nik as evaluator_nik',
                'evaluators.name as evaluator_name',
                'evaluator_positions.name as evaluator_position',
            ])
            ->selectSub(function (QueryBuilder $query): void {
                $query->from('department_user')
                    ->join('departments', 'departments.id', '=', 'department_user.department_id')
                    ->whereColumn('department_user.user_id', 'evaluators.id')
                    ->selectRaw('GROUP_CONCAT(DISTINCT departments.name)');
            }, 'evaluator_departments')
            ->selectSub(function (QueryBuilder $query): void {
                $query->from('factory_user')
                    ->join('factories', 'factories.id', '=', 'factory_user.factory_id')
                    ->whereColumn('factory_user.user_id', 'evaluators.id')
                    ->selectRaw('GROUP_CONCAT(DISTINCT factories.name)');
            }, 'evaluator_factories')
            ->orderBy('targets.name')
            ->orderBy('evaluators.name');

        return $query;
    }
}
