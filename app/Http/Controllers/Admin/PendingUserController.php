<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
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
                ->get(['id', 'nik', 'name', 'email', 'requested_role', 'created_at']),
        ]);
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
