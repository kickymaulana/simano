<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DepartmentRequest;
use App\Models\Department;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Departments/Index', [
            'departments' => Department::query()->orderBy('name')->paginate(10)->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Departments/Form', ['department' => null]);
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        $department = Department::create($request->validated());
        $this->audit->record('department.created', $department, $department->only(['name']));

        return to_route('admin.departments.index')->with('success', 'Departemen dibuat.');
    }

    public function edit(Department $department): Response
    {
        return Inertia::render('Admin/Departments/Form', ['department' => $department]);
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());
        $this->audit->record('department.updated', $department->fresh(), $department->only(['name']));

        return to_route('admin.departments.index')->with('success', 'Departemen diperbarui.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $snapshot = $department->only(['name']);
        $department->delete();
        $this->audit->record('department.deleted', null, $snapshot);

        return to_route('admin.departments.index')->with('success', 'Departemen dihapus.');
    }
}
