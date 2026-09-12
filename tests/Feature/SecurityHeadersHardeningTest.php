<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SecurityHeadersHardeningTest extends TestCase
{
    public function test_baseline_browser_security_headers_are_present_and_camera_is_self_only(): void
    {
        config([
            'smartfind_security.headers.permissions_policy' =>
                'camera=(self), microphone=(), geolocation=(), payment=(), usb=()',
            'smartfind_security.csp.enabled' => false,
            'smartfind_security.hsts.enabled' => false,
        ]);

        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin')
            ->assertHeader('X-Permitted-Cross-Domain-Policies', 'none')
            ->assertHeader('X-DNS-Prefetch-Control', 'off');

        $permissionsPolicy = (string) $response->headers->get(
            'Permissions-Policy',
            ''
        );

        $this->assertStringContainsString(
            'camera=(self)',
            $permissionsPolicy
        );

        $this->assertStringNotContainsString(
            'camera=()',
            $permissionsPolicy
        );

        $this->assertStringContainsString(
            'microphone=()',
            $permissionsPolicy
        );

        $this->assertStringContainsString(
            'geolocation=()',
            $permissionsPolicy
        );
    }

    public function test_csp_is_not_emitted_when_disabled(): void
    {
        config([
            'smartfind_security.csp.enabled' => false,
        ]);

        $response = $this->get('/login');

        $this->assertFalse(
            $response->headers->has('Content-Security-Policy')
        );
    }

    public function test_csp_is_emitted_when_enabled(): void
    {
        config([
            'smartfind_security.csp.enabled' => true,
            'smartfind_security.csp.policy' =>
                "default-src 'self'; object-src 'none'; frame-ancestors 'none'",
        ]);

        $response = $this->get('/login');

        $response->assertHeader(
            'Content-Security-Policy',
            "default-src 'self'; object-src 'none'; frame-ancestors 'none'"
        );
    }

    public function test_hsts_is_never_emitted_on_plain_http(): void
    {
        config([
            'smartfind_security.hsts.enabled' => true,
            'smartfind_security.hsts.max_age' => 31536000,
        ]);

        $response = $this->get('/login');

        $this->assertFalse(
            $response->headers->has('Strict-Transport-Security')
        );
    }

    public function test_hsts_is_emitted_only_for_https_when_enabled(): void
    {
        config([
            'smartfind_security.hsts.enabled' => true,
            'smartfind_security.hsts.max_age' => 31536000,
            'smartfind_security.hsts.include_subdomains' => false,
            'smartfind_security.hsts.preload' => false,
        ]);

        /*
        | Feature request helper Laravel tidak selalu mensimulasikan HTTPS
        | dengan cara yang sama seperti web server / reverse proxy nyata.
        | Untuk branch HSTS kita uji middleware secara langsung menggunakan
        | Request Symfony yang benar-benar memiliki scheme https://.
        */
        $request = Request::create(
            'https://qualityhub.test/login',
            'GET'
        );

        $response = (new SecurityHeaders())->handle(
            $request,
            fn (Request $request): Response => response('OK')
        );

        $this->assertSame(
            'max-age=31536000',
            $response->headers->get('Strict-Transport-Security')
        );
    }
}
