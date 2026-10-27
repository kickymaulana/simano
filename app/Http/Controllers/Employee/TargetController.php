<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\TargetSearchRequest;
use App\Models\Department;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationRule;
use App\Models\Factory;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class TargetController extends Controller
{
    public function index(TargetSearchRequest $request): Response
    {
        $periodId = EvaluationPeriod::active()->value('id');
        $evaluatedTargetIds = $periodId
            ? $request->user()->submittedEvaluations()->where('evaluation_period_id', $periodId)->pluck('target_id')->all()
            : [];

        $evaluator = $request->user()->load(['position', 'departments', 'factories']);
        $hrdRuleExists = $evaluator->position
            ? EvaluationRule::query()->where('evaluator_position_id', $evaluator->position->id)->where('target_category', 'hrd')->whereNull('target_position_id')->where('active', true)->exists()
            : false;
        $targetPositionIds = $evaluator->position
            ? EvaluationRule::query()->where('evaluator_position_id', $evaluator->position->id)->whereNull('target_category')->whereNotNull('target_position_id')->where('active', true)->pluck('target_position_id')
            : collect();
        $departmentIds = $evaluator->departments->pluck('id');
        $factoryIds = $evaluator->factories->pluck('id');

        $query = User::query()
            ->with(['position', 'departments', 'factories', 'evaluationTemplate'])
            ->where('active', true)
            ->where('id', '!=', $request->user()->getKey())
            ->where(function (Builder $query) use ($hrdRuleExists, $targetPositionIds, $departmentIds, $factoryIds): void {
                $query->where(function (Builder $query) use ($hrdRuleExists): void {
                    $query->whereHas('evaluationTemplate', fn (Builder $q) => $q->where('target_category', 'hrd'))
                        ->when(! $hrdRuleExists, fn (Builder $q) => $q->whereRaw('1 = 0'));
                })->orWhere(function (Builder $query) use ($targetPositionIds, $departmentIds, $factoryIds): void {
                    $query->whereIn('position_id', $targetPositionIds)
                        ->when($departmentIds->isNotEmpty(), fn (Builder $q) => $q->whereHas('departments', fn (Builder $departmentQuery) => $departmentQuery->whereIn('departments.id', $departmentIds)))
                        ->when($factoryIds->isNotEmpty(), fn (Builder $q) => $q->whereHas('factories', fn (Builder $factoryQuery) => $factoryQuery->whereIn('factories.id', $factoryIds)));
                });
            })
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $term = $request->string('q')->toString();
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('nik', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('position_id'), fn (Builder $query) => $query->where('position_id', $request->integer('position_id')))
            ->when($request->filled('department_id'), fn (Builder $query) => $query->whereHas('departments', fn (Builder $q) => $q->whereKey($request->integer('department_id'))))
            ->when($request->filled('factory_id'), fn (Builder $query) => $query->whereHas('factories', fn (Builder $q) => $q->whereKey($request->integer('factory_id'))))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Targets/Index', [
            'filters' => $request->only(['q', 'position_id', 'department_id', 'factory_id']),
            'positions' => Position::query()->orderBy('level')->orderBy('name')->get(['id', 'name']),
            'factories' => Factory::query()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'targets' => collect($query->items())->map(function (User $target) use ($evaluatedTargetIds): array {
                return array_merge($target->toArray(), ['evaluated' => in_array($target->id, $evaluatedTargetIds, true)]);
            })->values(),
            'pagination' => [
                'current_page' => $query->currentPage(),
                'last_page' => $query->lastPage(),
                'total' => $query->total(),
            ],
        ]);
    }
}
