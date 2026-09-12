<?php

namespace App\Http\Controllers\Sso;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            'role' => ['required', 'in:employee,admin,hr'],
        ]);

        $user->update(['requested_role' => $validated['role']]);
        $request->session()->forget('pending_user_id');

        return redirect()->route('sso.login')->withErrors([
            'sso' => 'Permintaan role terkirim. Silakan tunggu persetujuan admin.',
        ]);
    }
}
