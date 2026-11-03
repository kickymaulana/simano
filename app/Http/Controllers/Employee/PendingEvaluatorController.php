<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\EvaluationPeriod;
use App\Models\User;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PendingEvaluatorController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate(['period' => ['nullable', 'integer', 'exists:evaluation_periods,id']]);
        $user = $request->user()->load(['position', 'evaluationTemplate']);
        $period = EvaluationPeriod::query()
            ->when($validated['period'] ?? null, fn ($query, int $id) => $query->whereKey($id))
            ->orderByDesc('year')->orderByDesc('month')->first();
        $pendingEvaluators = $period ? $this->pendingQuery($user, $period)->paginate(10)->withQueryString() : new LengthAwarePaginator([], 0, 10, 1, ['path' => $request->url(), 'query' => $request->query()]);

        return Inertia::render('Profile/PendingEvaluators', [
            'user' => $user->only(['id', 'name']),
            'periods' => EvaluationPeriod::query()->orderByDesc('year')->orderByDesc('month')->get(['id', 'month', 'year', 'status']),
            'selectedPeriod' => $period?->only(['id', 'month', 'year', 'status']),
            'pendingEvaluators' => $pendingEvaluators,
        ]);
    }

    private function pendingQuery(User $target, EvaluationPeriod $period): QueryBuilder
    {
        $targetCategory = strtolower((string) $target->evaluationTemplate?->target_category);

        return DB::table('users as evaluators')
            ->join('evaluation_rules', function ($join): void {
                $join->on('evaluation_rules.evaluator_position_id', '=', 'evaluators.position_id')
                    ->where('evaluation_rules.active', true);
            })
            ->leftJoin('positions', 'positions.id', '=', 'evaluators.position_id')
            ->where('evaluators.active', true)
            ->where('evaluators.is_approved', true)
            ->where('evaluators.id', '!=', $target->id)
            ->where(function (QueryBuilder $query) use ($target, $targetCategory): void {
                if ($targetCategory === 'hrd') {
                    $query->where('evaluation_rules.target_category', 'hrd')->whereNull('evaluation_rules.target_position_id');

                    return;
                }

                $query->whereNull('evaluation_rules.target_category')->where('evaluation_rules.target_position_id', $target->position_id);
            })
            ->where(function (QueryBuilder $query) use ($target): void {
                $query->where('evaluation_rules.same_department', false)
                    ->orWhereExists(function (QueryBuilder $query) use ($target): void {
                        $query->selectRaw('1')->from('department_user as target_departments')
                            ->where('target_departments.user_id', $target->id)
                            ->whereExists(fn (QueryBuilder $matching) => $matching->selectRaw('1')->from('department_user as evaluator_departments')->whereColumn('evaluator_departments.user_id', 'evaluators.id')->whereColumn('evaluator_departments.department_id', 'target_departments.department_id'));
                    });
            })
            ->where(function (QueryBuilder $query) use ($target): void {
                $query->where('evaluation_rules.same_factory', false)
                    ->orWhereExists(function (QueryBuilder $query) use ($target): void {
                        $query->selectRaw('1')->from('factory_user as target_factories')
                            ->where('target_factories.user_id', $target->id)
                            ->whereExists(fn (QueryBuilder $matching) => $matching->selectRaw('1')->from('factory_user as evaluator_factories')->whereColumn('evaluator_factories.user_id', 'evaluators.id')->whereColumn('evaluator_factories.factory_id', 'target_factories.factory_id'));
                    });
            })
            ->whereNotExists(function (QueryBuilder $query) use ($target, $period): void {
                $query->selectRaw('1')->from('evaluations')
                    ->whereColumn('evaluations.evaluator_id', 'evaluators.id')
                    ->where('evaluations.target_id', $target->id)
                    ->where('evaluations.evaluation_period_id', $period->id);
            })
            ->select(['evaluators.id', 'evaluators.nik', 'evaluators.name', 'positions.name as position'])
            ->selectSub(fn (QueryBuilder $query) => $query->from('department_user')->join('departments', 'departments.id', '=', 'department_user.department_id')->whereColumn('department_user.user_id', 'evaluators.id')->selectRaw('GROUP_CONCAT(DISTINCT departments.name)'), 'departments')
            ->selectSub(fn (QueryBuilder $query) => $query->from('factory_user')->join('factories', 'factories.id', '=', 'factory_user.factory_id')->whereColumn('factory_user.user_id', 'evaluators.id')->selectRaw('GROUP_CONCAT(DISTINCT factories.name)'), 'factories')
            ->distinct()
            ->orderBy('evaluators.name');
    }
}
