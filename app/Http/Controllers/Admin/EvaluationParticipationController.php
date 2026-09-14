<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EvaluationReportRequest;
use App\Models\Department;
use App\Models\EvaluationPeriod;
use App\Models\Factory;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationParticipationController extends Controller
{
    public function __invoke(EvaluationReportRequest $request): Response
    {
        $period = EvaluationPeriod::query()
            ->when($request->filled('period'), fn (Builder $query) => $query->whereKey($request->integer('period')))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        $users = User::query()
            ->with(['position:id,name', 'departments:id,name', 'factories:id,name'])
            ->where('active', true)
            ->where('is_approved', true)
            ->when($request->filled('position_id'), fn (Builder $query) => $query->where('position_id', $request->integer('position_id')))
            ->when($request->filled('factory_id'), fn (Builder $query) => $query->whereHas('factories', fn (Builder $factory) => $factory->whereKey($request->integer('factory_id'))))
            ->when($request->filled('department_id'), fn (Builder $query) => $query->whereHas('departments', fn (Builder $department) => $department->whereKey($request->integer('department_id'))))
            ->withCount(['receivedEvaluations as evaluation_count' => fn (Builder $query) => $query->when($period, fn (Builder $query) => $query->where('evaluation_period_id', $period->id))])
            ->withCount(['receivedEvaluations as evaluator_count' => fn (Builder $query) => $query
                ->when($period, fn (Builder $query) => $query->where('evaluation_period_id', $period->id))
                ->selectRaw('COUNT(DISTINCT evaluator_id)')])
            ->withMax(['receivedEvaluations as last_evaluated_at' => fn (Builder $query) => $query->when($period, fn (Builder $query) => $query->where('evaluation_period_id', $period->id))], 'submitted_at')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Participation/Index', [
            'periods' => EvaluationPeriod::query()->orderByDesc('year')->orderByDesc('month')->get(['id', 'month', 'year', 'status']),
            'selectedPeriod' => $period?->only(['id', 'month', 'year', 'status']),
            'positions' => Position::query()->orderBy('level')->orderBy('name')->get(['id', 'name']),
            'factories' => Factory::query()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'filters' => collect(['position_id', 'factory_id', 'department_id'])->mapWithKeys(fn (string $key) => [$key => $request->filled($key) ? $request->integer($key) : null]),
            'users' => $users,
        ]);
    }
}
