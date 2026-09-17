<?php

namespace App\Http\Controllers\Sso;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Factory;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PendingRoleController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $user = User::find($request->session()->get('pending_user_id'));

        if (! $user || $user->is_approved) {
            $request->session()->forget('pending_user_id');

            return redirect()->route('sso.login');
        }

        if ($user->requested_role) {
            $request->session()->forget('pending_user_id');

            return redirect()->route('sso.login')->withErrors([
                'sso' => 'Permintaan role sedang menunggu persetujuan admin.',
            ]);
        }

        return Inertia::render('Auth/PendingRole', [
            'user' => $user->only(['name', 'nik']),
            'positions' => Position::query()->orderBy('level')->orderBy('name')->get(['id', 'name']),
            'factories' => Factory::query()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = User::find($request->session()->get('pending_user_id'));

        if (! $user || $user->is_approved) {
            $request->session()->forget('pending_user_id');

            return redirect()->route('sso.login');
        }

        $validated = $request->validate([
            'role' => ['required', 'in:employee'],
            'position_id' => ['required', 'exists:positions,id'],
            'department_ids' => ['required', 'array', 'min:1'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
            'factory_ids' => ['required', 'array', 'min:1'],
            'factory_ids.*' => ['integer', 'exists:factories,id'],
        ]);

        $user->update([
            'requested_role' => $validated['role'],
            'requested_position_id' => $validated['position_id'],
            'requested_department_id' => $validated['department_ids'][0],
        ]);
        $user->requestedDepartments()->sync($validated['department_ids']);
        $user->requestedFactories()->sync($validated['factory_ids']);
        $request->session()->forget('pending_user_id');

        return redirect()->route('sso.login')->withErrors([
            'sso' => 'Permintaan role terkirim. Silakan tunggu persetujuan admin.',
        ]);
    }
}
