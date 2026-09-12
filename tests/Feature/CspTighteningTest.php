<?php

namespace Tests\Feature;

use Tests\TestCase;

class CspTighteningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'smartfind_security.csp.enabled' => true,
        ]);
    }

    public function test_script_and_style_elements_use_nonce_without_broad_unsafe_inline(): void
    {
        $policy = (string) config('smartfind_security.csp.policy');

        $scriptDirective = $this->directive($policy, 'script-src');
        $styleDirective = $this->directive($policy, 'style-src');
        $styleElementDirective = $this->directive($policy, 'style-src-elem');
        $styleAttributeDirective = $this->directive($policy, 'style-src-attr');

        $this->assertStringContainsString("'self'", $scriptDirective);
        $this->assertStringContainsString('__SMARTFIND_CSP_NONCE__', $scriptDirective);
        $this->assertStringNotContainsString("'unsafe-inline'", $scriptDirective);
        $this->assertStringNotContainsString("'unsafe-eval'", $scriptDirective);

        $this->assertSame("style-src 'self'", $styleDirective);
        $this->assertStringNotContainsString("'unsafe-inline'", $styleDirective);

        $this->assertStringContainsString("'self'", $styleElementDirective);
        $this->assertStringContainsString('__SMARTFIND_CSP_NONCE__', $styleElementDirective);
        $this->assertStringNotContainsString("'unsafe-inline'", $styleElementDirective);

        $this->assertSame(
            "style-src-attr 'none'",
            $styleAttributeDirective
        );

        $this->assertStringContainsString("img-src 'self' data: blob:", $policy);
        $this->assertStringContainsString("connect-src 'self'", $policy);
    }

    public function test_response_uses_same_per_response_nonce_for_script_and_style_elements(): void
    {
        $response = $this->get('/login');

        $response->assertOk();

        $policy = (string) $response->headers->get('Content-Security-Policy');

        $this->assertNotSame('', $policy);
        $this->assertStringNotContainsString('__SMARTFIND_CSP_NONCE__', $policy);

        $scriptNonce = $this->extractNonce($policy, 'script-src');
        $styleNonce = $this->extractNonce($policy, 'style-src-elem');

        $this->assertNotSame('', $scriptNonce);
        $this->assertSame($scriptNonce, $styleNonce);

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

        $html = (string) $response->getContent();

        $this->assertStringContainsString(
            '<style nonce="' . $scriptNonce . '"',
            $html
        );
        $this->assertStringContainsString(
            'nonce="' . $scriptNonce . '"',
            $html
        );
    }

    public function test_nonce_is_unique_between_responses(): void
    {
        $first = $this->get('/login');
        $second = $this->get('/login');

        $firstNonce = $this->extractNonce(
            (string) $first->headers->get('Content-Security-Policy'),
            'script-src'
        );
        $secondNonce = $this->extractNonce(
            (string) $second->headers->get('Content-Security-Policy'),
            'script-src'
        );

        $this->assertNotSame('', $firstNonce);
        $this->assertNotSame('', $secondNonce);
        $this->assertNotSame($firstNonce, $secondNonce);
    }

    public function test_permissions_policy_keeps_camera_self_only(): void
    {
        $response = $this->get('/login');

        $permissions = (string) $response->headers->get('Permissions-Policy');

        $this->assertStringContainsString('camera=(self)', $permissions);
        $this->assertStringNotContainsString('camera=()', $permissions);
    }

    public function test_frontend_entry_uses_alpine_csp_build(): void
    {
        $path = resource_path('js/app.js');

        $this->assertFileExists($path);

        $source = (string) file_get_contents($path);

        $this->assertStringContainsString('@alpinejs/csp', $source);
        $this->assertDoesNotMatchRegularExpression(
            "/from\\s+['\"]alpinejs['\"]/",
            $source
        );
    }

    public function test_package_json_declares_alpine_csp_dependency(): void
    {
        $path = base_path('package.json');

        $this->assertFileExists($path);

        $package = json_decode(
            (string) file_get_contents($path),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $dependencies = array_merge(
            $package['dependencies'] ?? [],
            $package['devDependencies'] ?? []
        );

        $this->assertArrayHasKey('@alpinejs/csp', $dependencies);
    }

    private function extractNonce(string $policy, string $directiveName): string
    {
        $directive = $this->directive($policy, $directiveName);

        if (
            preg_match(
                "/'nonce-([^']+)'/",
                $directive,
                $matches
            ) === 1
        ) {
            return $matches[1];
        }

        return '';
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
