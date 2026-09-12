<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Tests\TestCase;

class CspInlineScriptAuditTest extends TestCase
{
    public function test_every_inline_blade_script_has_csp_nonce(): void
    {
        $violations = [];

        foreach ($this->bladeFiles() as $path) {
            $source = (string) file_get_contents($path);

            preg_match_all(
                '/<script\\b([^>]*)>/is',
                $source,
                $matches,
                PREG_OFFSET_CAPTURE
            );

            foreach ($matches[0] as $index => [$tag, $offset]) {
                $attributes = $matches[1][$index][0] ?? '';

                // External same-origin scripts are already allowed by script-src 'self'.
                if (preg_match('/\\bsrc\\s*=/i', $attributes) === 1) {
                    continue;
                }

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

                $violations[] = $this->relative($path) . ':' . $line . ' => ' . trim($tag);
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Inline <script> tanpa nonce ditemukan:\n" . implode("\n", $violations)
        );
    }

    public function test_native_inline_event_handlers_are_not_used_in_blade_views(): void
    {
        $violations = [];

        foreach ($this->bladeFiles() as $path) {
            $source = (string) file_get_contents($path);

            if (
                preg_match_all(
                    '/\\son(?:click|change|submit|load|error|input|focus|blur|keydown|keyup|mouseover|mouseout)\\s*=/i',
                    $source,
                    $matches,
                    PREG_OFFSET_CAPTURE
                ) > 0
            ) {
                foreach ($matches[0] as [$match, $offset]) {
                    $line = substr_count(
                        substr($source, 0, $offset),
                        "\n"
                    ) + 1;

                    $violations[] = $this->relative($path) . ':' . $line . ' => ' . trim($match);
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Native inline event handler ditemukan:\n" . implode("\n", $violations)
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

    private function relative(string $path): string
    {
        return str_replace(
            '\\',
            '/',
            ltrim(str_replace(base_path(), '', $path), '\\/')
        );
    }
}
