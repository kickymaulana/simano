<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PendingUserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/PendingUsers/Index', [
            'users' => User::query()
                ->where('is_approved', false)
                ->whereNotNull('requested_role')
                ->latest('updated_at')
                ->get(['id', 'nik', 'name', 'email', 'requested_role', 'created_at']),
        ]);
    }

    public function approve(User $user): RedirectResponse
    {
        abort_unless($user->requested_role && in_array($user->requested_role, ['employee', 'admin', 'hr'], true), 422);

        $role = $user->requested_role;
        $user->update([
            'role' => $role,
            'is_approved' => true,
            'active' => true,
            'requested_role' => null,
        ]);
        $user->syncRoles([$role]);

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
