<?php

namespace Tests\Feature;

use Tests\TestCase;

class EarlySecurityHeadersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'smartfind_security.same_origin.enabled' => true,
            'smartfind_security.csp.enabled' => true,
            'smartfind_security.headers.permissions_policy' =>
                'camera=(self), microphone=(), geolocation=(), payment=(), usb=()',
        ]);
    }

    public function test_cross_origin_403_renders_with_nonce_and_security_headers(): void
    {
        $response = $this
            ->withHeaders([
                'Origin' => 'https://evil.example',
                'Referer' => 'https://evil.example/login',
                'Sec-Fetch-Site' => 'cross-site',
            ])
            ->post(
                '/login',
                [
                    'email' => 'nobody@example.com',
                    'password' => 'invalid-password',
                ]
            );

        $response->assertForbidden();

        $response->assertHeader('Content-Security-Policy');
        $response->assertHeader(
            'Permissions-Policy',
            'camera=(self), microphone=(), geolocation=(), payment=(), usb=()'
        );
        $response->assertHeader(
            'X-Content-Type-Options',
            'nosniff'
        );
        $response->assertHeader(
            'X-Frame-Options',
            'DENY'
        );
        $response->assertHeader(
            'Referrer-Policy',
            'no-referrer'
        );

        $policy =
            (string) $response
                ->headers
                ->get(
                    'Content-Security-Policy'
                );

        $this->assertStringNotContainsString(
            '__SMARTFIND_CSP_NONCE__',
            $policy
        );

        $this->assertMatchesRegularExpression(
            "/script-src 'self' 'nonce-[A-Za-z0-9+\\/=]+';/",
            $policy
        );

        $this->assertMatchesRegularExpression(
            "/style-src-elem 'self' 'nonce-[A-Za-z0-9+\\/=]+';/",
            $policy
        );

        preg_match(
            "/script-src 'self' 'nonce-([^']+)'/",
            $policy,
            $matches
        );

        $nonce = $matches[1] ?? '';

        $this->assertNotSame(
            '',
            $nonce
        );

        /*
        | errors/403.blade.php sudah memakai style nonce.
        | Jika SecurityHeaders terlambat dijalankan, kasus ini akan berubah
        | menjadi 500 "Undefined variable $cspNonce".
        */
        $this->assertStringContainsString(
            'nonce="' . $nonce . '"',
            (string) $response->getContent()
        );
    }

    public function test_normal_login_response_still_uses_one_consistent_nonce(): void
    {
        $response = $this->get('/login');

        $response->assertOk();

        $policy =
            (string) $response
                ->headers
                ->get(
                    'Content-Security-Policy'
                );

        preg_match(
            "/script-src 'self' 'nonce-([^']+)'/",
            $policy,
            $matches
        );

        $nonce = $matches[1] ?? '';

        $this->assertNotSame(
            '',
            $nonce
        );

        $this->assertStringContainsString(
            "style-src-elem 'self' 'nonce-{$nonce}'",
            $policy
        );

        $this->assertStringContainsString(
            'nonce="' . $nonce . '"',
            (string) $response->getContent()
        );
    }
}
