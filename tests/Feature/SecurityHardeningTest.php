<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    public function test_production_session_cookie_is_secure_by_default(): void
    {
        $this->assertTrue(config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));

        config(['app.env' => 'production', 'session.secure' => true]);

        $this->assertTrue(config('session.secure'));
    }

    public function test_force_https_redirects_insecure_requests_when_enabled(): void
    {
        config(['app.force_https' => true]);

        $this->get('/')->assertRedirect(url('/', [], true));
    }
}
