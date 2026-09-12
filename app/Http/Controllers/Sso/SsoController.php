<?php

namespace App\Http\Controllers\Sso;

use App\Http\Controllers\Controller;
use App\Services\Sso\SsoService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SsoController extends Controller
{
    public function __construct(private readonly SsoService $sso) {}

    public function login(Request $request): RedirectResponse|Response
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        if ($request->session()->get('errors')?->has('sso')) {
            return Inertia::render('Auth/Login');
        }

        try {
            return redirect()->away($this->sso->authorizationUrl());
        } catch (\RuntimeException $exception) {
            return Inertia::render('Auth/Login', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect()->route('sso.login')->withErrors([
                'sso' => $request->string('error_description')->toString() ?: 'Otorisasi SSO dibatalkan.',
            ]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $request->session()->forget(['sso.state', 'sso.nonce']);

        try {
            $token = $this->sso->exchangeCode($validated['code']);
            $profile = $this->sso->userFromToken($token['access_token'] ?? '');
            $user = $this->sso->syncUser($profile);

            if (! $user->is_approved) {
                if (! $user->requested_role) {
                    $request->session()->put('pending_user_id', $user->id);

                    return redirect()->route('pending-role');
                }

                return redirect()->route('sso.login')->withErrors([
                    'sso' => 'Akun menunggu persetujuan admin.',
                ]);
            }

            Auth::login($user, remember: false);
            $request->session()->regenerate();
            $request->session()->forget(['sso.state', 'sso.nonce']);

            return redirect()->intended(route('home'));
        } catch (\Throwable $exception) {
            Log::warning('SSO authentication failed', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'status' => $exception instanceof RequestException ? $exception->response?->status() : null,
                'response' => $exception instanceof RequestException ? $exception->response?->json() : null,
            ]);

            return redirect()->route('sso.login')->withErrors([
                'sso' => 'Login SSO gagal. Periksa konfigurasi OAuth client dan coba lagi.',
            ]);
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
