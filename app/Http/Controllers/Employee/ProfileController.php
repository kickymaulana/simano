<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\UpdateProfileRequest;
use App\Models\Department;
use App\Models\Factory;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user()->load(['position', 'departments', 'factories', 'requestedPosition', 'requestedDepartments', 'requestedFactories']);

        return Inertia::render('Profile/Edit', [
            'user' => $user->only(['name', 'nik', 'email', 'avatar_url', 'role']),
            'current' => [
                'position' => $user->position?->only(['id', 'name']),
                'departments' => $user->departments->map->only(['id', 'name'])->values(),
                'factories' => $user->factories->map->only(['id', 'name'])->values(),
            ],
            'request' => $user->requested_position_id ? [
                'position_id' => $user->requested_position_id,
                'department_ids' => $user->requestedDepartments->pluck('id'),
                'factory_ids' => $user->requestedFactories->pluck('id'),
            ] : null,
            'positions' => Position::query()->orderBy('level')->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'factories' => Factory::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        Position::findOrFail($validated['position_id']);
        $departmentIds = $validated['department_ids'];
        $factoryIds = $validated['factory_ids'];
        $user = $request->user();

        DB::transaction(function () use ($user, $validated, $departmentIds, $factoryIds): void {
            $user->update([
                'position_id' => $validated['position_id'],
                'department_id' => $departmentIds[0],
                'requested_role' => null,
                'requested_position_id' => null,
                'requested_department_id' => null,
            ]);
            $user->departments()->sync($departmentIds);
            $user->factories()->sync($factoryIds);
            $user->requestedDepartments()->detach();
            $user->requestedFactories()->detach();
        });

        return back()->with('success', 'Profil organisasi berhasil diperbarui.');
    }
}
