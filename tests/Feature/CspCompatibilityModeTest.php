<?php

namespace Tests\Feature;

use Tests\TestCase;

class CspCompatibilityModeTest extends TestCase
{
    public function test_current_csp_keeps_required_qualityhub_frontend_capabilities(): void
    {
        $policy = (string) config('smartfind_security.csp.policy');

        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("base-uri 'self'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
        $this->assertStringContainsString("form-action 'self'", $policy);
        $this->assertStringContainsString("img-src 'self' data: blob:", $policy);
        $this->assertStringContainsString("media-src 'self' blob:", $policy);

        $this->assertSame("style-src 'self'", $this->directive($policy, 'style-src'));
        $this->assertStringContainsString(
            "style-src-elem 'self' __SMARTFIND_CSP_NONCE__",
            $policy
        );
        $this->assertSame(
            "style-src-attr 'none'",
            $this->directive($policy, 'style-src-attr')
        );

        $this->assertStringContainsString(
            "script-src 'self' __SMARTFIND_CSP_NONCE__",
            $policy
        );
        $this->assertStringNotContainsString("'unsafe-eval'", $policy);

        $this->assertStringNotContainsString(
            "'unsafe-inline'",
            $this->directive($policy, 'script-src')
        );
        $this->assertStringNotContainsString(
            "'unsafe-inline'",
            $this->directive($policy, 'style-src')
        );
        $this->assertStringNotContainsString(
            "'unsafe-inline'",
            $this->directive($policy, 'style-src-elem')
        );

        $this->assertStringContainsString("connect-src 'self'", $policy);
        $this->assertStringContainsString("worker-src 'self' blob:", $policy);
        $this->assertStringContainsString("manifest-src 'self'", $policy);
        $this->assertStringNotContainsString('*', $policy);
        $this->assertStringNotContainsString('http:', $policy);
        $this->assertStringNotContainsString('https:', $policy);
    }

    public function test_enabled_csp_is_sent_with_per_response_nonce_and_camera_self_only(): void
    {
        config([
            'smartfind_security.csp.enabled' => true,
            'smartfind_security.headers.permissions_policy' =>
                'camera=(self), microphone=(), geolocation=(), payment=(), usb=()',
        ]);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertHeader('Content-Security-Policy');
        $response->assertHeader(
            'Permissions-Policy',
            'camera=(self), microphone=(), geolocation=(), payment=(), usb=()'
        );

        $header = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringNotContainsString('__SMARTFIND_CSP_NONCE__', $header);
        $this->assertMatchesRegularExpression(
            "/script-src 'self' 'nonce-[A-Za-z0-9+\\/=]+';/",
            $header
        );
        $this->assertMatchesRegularExpression(
            "/style-src-elem 'self' 'nonce-[A-Za-z0-9+\\/=]+';/",
            $header
        );
        $this->assertStringNotContainsString(
            'camera=()',
            (string) $response->headers->get('Permissions-Policy')
        );
    }

    public function test_hsts_remains_disabled_independently_from_csp_on_local_http(): void
    {
        config([
            'smartfind_security.csp.enabled' => true,
            'smartfind_security.hsts.enabled' => false,
        ]);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertHeader('Content-Security-Policy');
        $response->assertHeaderMissing('Strict-Transport-Security');
    }

    private function directive(string $policy, string $name): string
    {
        foreach (explode(';', $policy) as $directive) {
            $directive = trim($directive);

            if (
                $directive === $name
                || str_starts_with($directive, $name . ' ')
            ) {
                return $directive;
            }
        }

        return '';
    }
}
