<?php

namespace App\Services\Sso;

use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SsoService
{
    public function authorizationUrl(): string
    {
        $baseUrl = config('services.sso.base_url');

        if (! $baseUrl || ! config('services.sso.client_id')) {
            throw new RuntimeException('SSO belum dikonfigurasi.');
        }

        return rtrim($baseUrl, '/').'/oauth/authorize?'.http_build_query([
            'client_id' => config('services.sso.client_id'),
            'redirect_uri' => route('sso.callback'),
            'response_type' => 'code',
            'scope' => '',
        ]);
    }

    public function exchangeCode(string $code): array
    {
        $baseUrl = config('services.sso.base_url');

        if (! $baseUrl) {
            throw new RuntimeException('SSO belum dikonfigurasi.');
        }

        return $this->client()->asForm()->post(rtrim($baseUrl, '/').'/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.sso.client_id'),
            'client_secret' => config('services.sso.client_secret'),
            'redirect_uri' => route('sso.callback'),
            'code' => $code,
        ])->throw()->json();
    }

    public function userFromToken(string $token): array
    {
        $baseUrl = config('services.sso.base_url');

        if (! $baseUrl || ! $token) {
            throw new RuntimeException('Token SSO tidak valid.');
        }

        return $this->client()->withToken($token)->get(rtrim($baseUrl, '/').'/api/user')->throw()->json();
    }

    public function syncUser(array $profile): User
    {
        $map = config('services.sso.field_map');
        $nik = data_get($profile, $map['nik']);

        if (! $nik) {
            throw new RuntimeException('SSO tidak mengembalikan NIK.');
        }

        $user = User::firstOrNew(['nik' => (string) $nik]);
        $user->fill([
            'name' => data_get($profile, $map['name'], 'Pengguna SIMANO'),
            'avatar_url' => data_get($profile, $map['avatar_url']),
            'active' => (bool) data_get($profile, $map['active'], true),
        ]);
        $user->email ??= data_get($profile, 'email', $nik.'@sso');
        $user->password ??= str()->random(32);
        $user->is_approved ??= false;
        $user->save();

        if (! $user->active) {
            throw new RuntimeException('Akun SSO tidak aktif.');
        }

        return $user;
    }

    public function createState(): string
    {
        return Str::random(64);
    }

    public function createNonce(): string
    {
        return Str::random(64);
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()->timeout(10);
    }
}
