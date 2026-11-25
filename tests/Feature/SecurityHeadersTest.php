<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_added_to_web_responses(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('X-XSS-Protection', '0')
            ->assertHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
    }

    public function test_http_requests_redirect_to_https_when_forced(): void
    {
        config(['app.force_https' => true]);

        $response = $this->get('http://example.test/');

        $response->assertStatus(301);
        $this->assertStringStartsWith('https://', $response->headers->get('Location'));
    }

    public function test_https_requests_are_not_redirected_when_forced(): void
    {
        config(['app.force_https' => true]);

        $this->get('https://example.test/')
            ->assertOk();
    }
}
