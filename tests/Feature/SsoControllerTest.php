<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Factory;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SsoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'inertia.ssr.enabled' => false,
            'app.url' => 'http://localhost',
            'app.force_https' => false,
            'services.sso.base_url' => 'https://sso.example.test',
            'services.sso.client_id' => 'test-client',
            'services.sso.client_secret' => 'test-secret',
            'services.sso.field_map' => [
                'nik' => 'nik',
                'name' => 'name',
                'avatar_url' => 'avatar_url',
                'active' => 'active',
            ],
        ]);
        $this->app['url']->useOrigin('http://localhost');
        Http::preventStrayRequests();
        $this->withoutVite();
    }

    public function test_normal_login_redirects_to_oauth(): void
    {
        $this->get(route('sso.login'))
            ->assertRedirectContains('https://sso.example.test/oauth/authorize?client_id=test-client&');

        $this->assertGuest();
    }

    public function test_pending_callback_settles_on_login_without_authenticating(): void
    {
        $user = User::factory()->create(['nik' => 'PENDING', 'is_approved' => false, 'requested_role' => 'employee']);
        Http::fake([
            'https://sso.example.test/oauth/token' => Http::response(['access_token' => 'test-token']),
            'https://sso.example.test/api/user' => Http::response(['nik' => $user->nik, 'name' => $user->name]),
        ]);

        $this->get(route('sso.callback', ['code' => 'test-code']))
            ->assertRedirect(route('sso.login'))
            ->assertSessionHasErrors(['sso' => 'Akun menunggu persetujuan admin.']);

        $this->assertLoginError('Akun menunggu persetujuan admin.');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_approved' => false, 'requested_role' => 'employee']);
        Http::assertSentCount(2);
    }

    public function test_failed_callback_settles_on_login_and_explicit_retry_restarts_oauth(): void
    {
        Http::fake([
            'https://sso.example.test/oauth/token' => Http::response(['error' => 'invalid_grant'], 400),
        ]);

        $this->get(route('sso.callback', ['code' => 'invalid-code']))
            ->assertRedirect(route('sso.login'))
            ->assertSessionHasErrors(['sso' => 'Login SSO gagal. Periksa konfigurasi OAuth client dan coba lagi.']);

        $this->assertLoginError('Login SSO gagal. Periksa konfigurasi OAuth client dan coba lagi.');
        $this->get(route('sso.login'))
            ->assertRedirectContains('https://sso.example.test/oauth/authorize?client_id=test-client&');
        Http::assertSentCount(1);
    }

    public function test_cancelled_callback_settles_on_login(): void
    {
        $this->get(route('sso.callback', ['error' => 'access_denied']))
            ->assertRedirect(route('sso.login'))
            ->assertSessionHasErrors(['sso' => 'Otorisasi SSO dibatalkan.']);

        $this->assertLoginError('Otorisasi SSO dibatalkan.');
    }

    public function test_role_request_settles_on_login_without_bypassing_approval(): void
    {
        $user = User::factory()->create(['is_approved' => false, 'requested_role' => null]);
        $position = Position::factory()->create();
        $department = Department::factory()->create();
        $factory = Factory::factory()->create();

        $this->withSession(['pending_user_id' => $user->id])
            ->post(route('pending-role.store'), [
                'role' => 'employee',
                'position_id' => $position->id,
                'department_ids' => [$department->id],
                'factory_ids' => [$factory->id],
            ])
            ->assertRedirect(route('sso.login'))
            ->assertSessionMissing('pending_user_id')
            ->assertSessionHasErrors(['sso' => 'Permintaan role terkirim. Silakan tunggu persetujuan admin.']);

        $this->assertLoginError('Permintaan role terkirim. Silakan tunggu persetujuan admin.');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'requested_role' => 'employee',
            'requested_position_id' => $position->id,
            'requested_department_id' => $department->id,
            'is_approved' => false,
        ]);
        $this->assertDatabaseHas('requested_factory_user', ['user_id' => $user->id, 'factory_id' => $factory->id]);
        $this->assertDatabaseHas('requested_department_user', ['user_id' => $user->id, 'department_id' => $department->id]);
    }

    public function test_approved_callback_authenticates_and_preserves_intended_redirect(): void
    {
        $user = User::factory()->create(['nik' => 'APPROVED', 'is_approved' => true]);
        Http::fake([
            'https://sso.example.test/oauth/token' => Http::response(['access_token' => 'test-token']),
            'https://sso.example.test/api/user' => Http::response(['nik' => $user->nik, 'name' => $user->name]),
        ]);

        $this->withSession(['url.intended' => route('dashboard')])
            ->get(route('sso.callback', ['code' => 'test-code']))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        Http::assertSentCount(2);
    }

    private function assertLoginError(string $message): void
    {
        $this->withCookie(config('session.cookie'), session()->getId())
            ->get(route('sso.login'))
            ->assertOk()
            ->assertHeaderMissing('Location')
            ->assertViewHas('page.component', 'Auth/Login')
            ->assertViewHas('page.props.errors', fn (object $errors): bool => $errors->sso === $message);

        $this->assertGuest();
    }
}
