<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Factory;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PendingUserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/PendingUsers/Index', [
            'users' => User::query()
                ->with(['requestedPosition', 'requestedDepartments', 'requestedFactories'])
                ->where('is_approved', false)
                ->whereNotNull('requested_role')
                ->latest('updated_at')
                ->paginate(10, ['id', 'nik', 'name', 'email', 'avatar_url', 'requested_role', 'requested_position_id', 'created_at'])
                ->through(fn (User $user): User => $user->makeVisible(['nik'])),
        ]);
    }

    public function edit(User $user): Response
    {
        abort_unless(! $user->is_approved && $user->requested_role, 404);

        return Inertia::render('Admin/PendingUsers/Form', [
            'user' => $user->load(['requestedPosition', 'requestedDepartments', 'requestedFactories']),
            'positions' => Position::query()->orderBy('level')->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'factories' => Factory::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(! $user->is_approved && $user->requested_role, 404);

        $validated = $request->validate([
            'role' => ['required', 'in:employee,admin,hr'],
            'position_id' => ['required', 'exists:positions,id'],
            'department_ids' => ['required', 'array', 'min:1'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
            'factory_ids' => ['required', 'array', 'min:1'],
            'factory_ids.*' => ['integer', 'exists:factories,id'],
        ]);
        $position = Position::findOrFail($validated['position_id']);
        $multiple = in_array(strtoupper($position->name), ['DIREKSI', 'GM/FM', 'FM', 'SEKRETARIS', 'MANAGER'], true);
        $departmentIds = $multiple ? $validated['department_ids'] : [$validated['department_ids'][0]];
        $factoryIds = $multiple ? $validated['factory_ids'] : [$validated['factory_ids'][0]];

        DB::transaction(function () use ($user, $validated, $departmentIds, $factoryIds): void {
            $user->update([
                'requested_role' => $validated['role'],
                'requested_position_id' => $validated['position_id'],
                'requested_department_id' => $departmentIds[0],
            ]);
            $user->requestedDepartments()->sync($departmentIds);
            $user->requestedFactories()->sync($factoryIds);
        });

        return to_route('admin.pending-users.index')->with('success', 'Pengajuan user diperbarui.');
    }

    public function approve(User $user): RedirectResponse
    {
        abort_unless($user->requested_role && in_array($user->requested_role, ['employee', 'admin', 'hr'], true), 422);

        DB::transaction(function () use ($user): void {
            $role = $user->requested_role;
            $departmentIds = $user->requestedDepartments()->pluck('requested_department_user.department_id');
            $user->update([
                'role' => $role,
                'position_id' => $user->requested_position_id,
                'department_id' => $departmentIds->first(),
                'requested_position_id' => null,
                'requested_department_id' => null,
                'is_approved' => true,
                'active' => true,
                'requested_role' => null,
            ]);
            $user->syncRoles([$role]);
            $user->departments()->sync($departmentIds);
            $user->requestedDepartments()->detach();
            $user->factories()->sync($user->requestedFactories()->pluck('requested_factory_user.factory_id'));
            $user->requestedFactories()->detach();
        });

        return back()->with('success', 'User berhasil disetujui.');
    }

    public function reject(User $user): RedirectResponse
    {
        $user->update([
            'is_approved' => false,
            'requested_role' => null,
        ]);

        return back()->with('success', 'Permintaan role ditolak.');
    }
}
