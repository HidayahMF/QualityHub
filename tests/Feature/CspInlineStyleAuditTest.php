<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Tests\TestCase;

class CspInlineStyleAuditTest extends TestCase
{
    public function test_every_inline_blade_style_block_has_csp_nonce(): void
    {
        $violations = [];

        foreach ($this->bladeFiles() as $path) {
            $source = (string) file_get_contents($path);

            preg_match_all(
                '/<style\\b([^>]*)>/is',
                $source,
                $matches,
                PREG_OFFSET_CAPTURE
            );

            foreach ($matches[0] as $index => [$tag, $offset]) {
                $attributes = $matches[1][$index][0] ?? '';

                if (
                    preg_match(
                        '/\\bnonce\\s*=\\s*["\']\\{\\{\\s*\\$cspNonce\\s*\\}\\}["\']/i',
                        $attributes
                    ) === 1
                ) {
                    continue;
                }

                $line = substr_count(
                    substr($source, 0, $offset),
                    "\n"
                ) + 1;

                $violations[] =
                    $this->relative($path)
                    . ':'
                    . $line
                    . ' => '
                    . trim($tag);
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Inline <style> tanpa nonce ditemukan:\n" . implode("\n", $violations)
        );
    }

    public function test_style_attributes_are_fully_blocked_by_csp(): void
    {
        $policy = (string) app('config')->get(
            'smartfind_security.csp.policy'
        );

        $styleSrc = $this->directive($policy, 'style-src');
        $styleElem = $this->directive($policy, 'style-src-elem');
        $styleAttr = $this->directive($policy, 'style-src-attr');

        $this->assertSame("style-src 'self'", $styleSrc);
        $this->assertStringNotContainsString("'unsafe-inline'", $styleSrc);

        $this->assertStringContainsString(
            '__SMARTFIND_CSP_NONCE__',
            $styleElem
        );
        $this->assertStringNotContainsString(
            "'unsafe-inline'",
            $styleElem
        );

        $this->assertSame(
            "style-src-attr 'none'",
            $styleAttr
        );
    }

    /** @return array<int, string> */
    private function bladeFiles(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                resource_path('views'),
                RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        $regex = new RegexIterator(
            $iterator,
            '/\\.blade\\.php$/i'
        );

        $files = [];

        foreach ($regex as $file) {
            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
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

    private function relative(string $path): string
    {
        return str_replace(
            '\\',
            '/',
            ltrim(str_replace(base_path(), '', $path), '\\/')
        );
    }
}
