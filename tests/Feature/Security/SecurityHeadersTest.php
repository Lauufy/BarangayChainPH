<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_normal_response_has_security_headers(): void
    {
        config(['security.csp.enforce' => false]);

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(self), geolocation=(), microphone=()');
        $response->assertHeader('Content-Security-Policy-Report-Only');
    }

    public function test_csp_enforcement_uses_enforcing_header(): void
    {
        config(['security.csp.enforce' => true]);

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertHeader('Content-Security-Policy');
        $response->assertHeaderMissing('Content-Security-Policy-Report-Only');
    }

    public function test_production_https_response_has_hsts(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        $response = $this->withServerVariables([
            'HTTPS' => 'on',
            'SERVER_PORT' => '443',
        ])->get('https://localhost/login');

        $response->assertOk();
        $response->assertHeader(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains'
        );
    }

    public function test_local_response_does_not_have_hsts(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        $this->app['env'] = 'local';
        config(['app.env' => 'local']);

        $response = $this->withServerVariables([
            'HTTPS' => 'on',
            'SERVER_PORT' => '443',
        ])->get('https://localhost/login');

        $response->assertOk();
        $response->assertHeaderMissing('Strict-Transport-Security');
    }
}
