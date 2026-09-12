<?php

namespace Tests\Feature;

use Tests\TestCase;

class CspNoUnsafeDirectivesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'smartfind_security.csp.enabled' => true,
        ]);
    }

    public function test_configured_policy_contains_no_unsafe_directives(): void
    {
        $policy = (string) app('config')->get(
            'smartfind_security.csp.policy'
        );

        $this->assertNotSame('', $policy);

        $this->assertStringNotContainsString(
            "'unsafe-inline'",
            $policy
        );

        $this->assertStringNotContainsString(
            "'unsafe-eval'",
            $policy
        );

        $this->assertSame(
            "style-src 'self'",
            $this->directive(
                $policy,
                'style-src'
            )
        );

        $this->assertSame(
            "style-src-attr 'none'",
            $this->directive(
                $policy,
                'style-src-attr'
            )
        );

        $this->assertStringContainsString(
            '__SMARTFIND_CSP_NONCE__',
            $this->directive(
                $policy,
                'style-src-elem'
            )
        );

        $this->assertStringContainsString(
            '__SMARTFIND_CSP_NONCE__',
            $this->directive(
                $policy,
                'script-src'
            )
        );
    }

    public function test_sent_csp_header_contains_no_unsafe_directives(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertHeader(
            'Content-Security-Policy'
        );

        $policy = (string) $response
            ->headers
            ->get(
                'Content-Security-Policy'
            );

        $this->assertStringNotContainsString(
            '__SMARTFIND_CSP_NONCE__',
            $policy
        );

        $this->assertStringNotContainsString(
            "'unsafe-inline'",
            $policy
        );

        $this->assertStringNotContainsString(
            "'unsafe-eval'",
            $policy
        );

        $this->assertSame(
            "style-src-attr 'none'",
            $this->directive(
                $policy,
                'style-src-attr'
            )
        );

        $scriptNonce = $this->extractNonce(
            $policy,
            'script-src'
        );

        $styleNonce = $this->extractNonce(
            $policy,
            'style-src-elem'
        );

        $this->assertNotSame(
            '',
            $scriptNonce
        );

        $this->assertSame(
            $scriptNonce,
            $styleNonce
        );
    }

    public function test_camera_and_blob_capabilities_remain_available(): void
    {
        $response = $this->get('/login');

        $response->assertOk();

        $policy = (string) $response
            ->headers
            ->get(
                'Content-Security-Policy'
            );

        $this->assertStringContainsString(
            "img-src 'self' data: blob:",
            $policy
        );

        $this->assertStringContainsString(
            "media-src 'self' blob:",
            $policy
        );

        $this->assertStringContainsString(
            "worker-src 'self' blob:",
            $policy
        );

        $response->assertHeader(
            'Permissions-Policy',
            'camera=(self), microphone=(), geolocation=(), payment=(), usb=()'
        );
    }

    private function directive(
        string $policy,
        string $name
    ): string {
        foreach (
            explode(';', $policy)
            as $directive
        ) {
            $directive = trim(
                $directive
            );

            if (
                $directive === $name
                || str_starts_with(
                    $directive,
                    $name . ' '
                )
            ) {
                return $directive;
            }
        }

        return '';
    }

    private function extractNonce(
        string $policy,
        string $directive
    ): string {
        $value = $this->directive(
            $policy,
            $directive
        );

        if (
            preg_match(
                "/'nonce-([^']+)'/",
                $value,
                $matches
            ) !== 1
        ) {
            return '';
        }

        return $matches[1];
    }
}
