<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EvaluationReportRequest;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\Factory;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class AttentionController extends Controller
{
    public function __invoke(EvaluationReportRequest $request): Response
    {
        $period = EvaluationPeriod::query()->when($request->filled('period'), fn (Builder $query) => $query->whereKey($request->integer('period')))->latest('year')->latest('month')->first();
        $threshold = (float) $request->input('threshold', 3);
        $previousPeriod = $period ? EvaluationPeriod::query()->where(fn (Builder $query) => $query->where('year', '<', $period->year)->orWhere(fn (Builder $query) => $query->where('year', $period->year)->where('month', '<', $period->month)))->latest('year')->latest('month')->first() : null;

        $query = Evaluation::query()->selectRaw('target_id, COUNT(*) as evaluation_count, ROUND(AVG(average_score), 2) as average_score')->with(['target.position:id,name', 'target.departments:id,name', 'target.factories:id,name'])->when($period, fn (Builder $query) => $query->where('evaluation_period_id', $period->id));
        $this->applyOrganizationFilters($query, $request);
        $rows = $query->groupBy('target_id')->havingRaw('AVG(average_score) < ?', [$threshold])->orderBy('average_score')->paginate(10)->withQueryString();

        $previousScores = $previousPeriod ? Evaluation::query()->where('evaluation_period_id', $previousPeriod->id)->selectRaw('target_id, ROUND(AVG(average_score), 2) as average_score')->groupBy('target_id')->pluck('average_score', 'target_id') : collect();
        $rows->through(fn (Evaluation $evaluation) => ['target' => [...$evaluation->target->only(['id', 'name', 'nik']), 'position' => $evaluation->target->position?->only(['id', 'name']), 'departments' => $evaluation->target->departments->map->only(['id', 'name'])->values(), 'factories' => $evaluation->target->factories->map->only(['id', 'name'])->values()], 'evaluation_count' => (int) $evaluation->evaluation_count, 'average_score' => (float) $evaluation->average_score, 'previous_average_score' => $previousScores->has($evaluation->target_id) ? (float) $previousScores[$evaluation->target_id] : null]);

        return Inertia::render('Admin/Attention/Index', ['periods' => EvaluationPeriod::query()->latest('year')->latest('month')->get(['id', 'month', 'year', 'status']), 'selectedPeriod' => $period?->only(['id', 'month', 'year', 'status']), 'positions' => Position::query()->orderBy('level')->orderBy('name')->get(['id', 'name']), 'factories' => Factory::query()->orderBy('name')->get(['id', 'name']), 'departments' => Department::query()->orderBy('name')->get(['id', 'name']), 'filters' => ['threshold' => $threshold, ...collect(['position_id', 'factory_id', 'department_id'])->mapWithKeys(fn (string $key) => [$key => $request->filled($key) ? $request->integer($key) : null])], 'rows' => $rows]);
    }

    private function applyOrganizationFilters(Builder $query, EvaluationReportRequest $request): void
    {
        if ($request->filled('position_id')) {
            $query->whereHas('target', fn (Builder $target) => $target->where('position_id', $request->integer('position_id')));
        }
        if ($request->filled('factory_id')) {
            $query->whereHas('target.factories', fn (Builder $factory) => $factory->whereKey($request->integer('factory_id')));
        }
        if ($request->filled('department_id')) {
            $query->whereHas('target.departments', fn (Builder $department) => $department->whereKey($request->integer('department_id')));
        }
    }
}
