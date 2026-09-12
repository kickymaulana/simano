<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\TargetSearchRequest;
use App\Models\Department;
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
        $query = User::query()
            ->with(['position', 'departments', 'factories'])
            ->where('active', true)
            ->where('id', '!=', $request->user()->getKey())
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
            'targets' => $query->items(),
            'pagination' => [
                'current_page' => $query->currentPage(),
                'last_page' => $query->lastPage(),
                'total' => $query->total(),
            ],
        ]);
    }
}
