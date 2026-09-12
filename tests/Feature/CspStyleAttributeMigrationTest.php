<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Tests\TestCase;

class CspStyleAttributeMigrationTest extends TestCase
{
    public function test_blade_views_no_longer_contain_native_style_attributes_or_style_bindings(): void
    {
        $violations = [];

        foreach ($this->bladeFiles() as $path) {
            $source = (string) file_get_contents($path);

            if (
                preg_match_all(
                    '/\bstyle\s*=\s*["\']/i',
                    $source,
                    $matches,
                    PREG_OFFSET_CAPTURE
                ) > 0
            ) {
                foreach ($matches[0] as [$match, $offset]) {
                    $violations[] =
                        $this->relative($path)
                        . ':'
                        . $this->lineNumber(
                            $source,
                            $offset
                        )
                        . ' => '
                        . $match;
                }
            }

            if (
                preg_match_all(
                    '/(?:x-bind:style|(?<![\w-]):style)\s*=/i',
                    $source,
                    $matches,
                    PREG_OFFSET_CAPTURE
                ) > 0
            ) {
                foreach ($matches[0] as [$match, $offset]) {
                    $violations[] =
                        $this->relative($path)
                        . ':'
                        . $this->lineNumber(
                            $source,
                            $offset
                        )
                        . ' => '
                        . $match;
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Style attribute/binding tersisa:\n"
                . implode("\n", $violations)
        );
    }

    public function test_javascript_does_not_use_blocked_style_attribute_apis(): void
    {
        $violations = [];

        foreach ($this->frontendSourceFiles() as $path) {
            $source = (string) file_get_contents($path);

            $patterns = [
                '/\.setAttribute\s*\(\s*["\']style["\']/i',
                '/\.style\.cssText\s*=/i',
            ];

            foreach ($patterns as $pattern) {
                if (
                    preg_match_all(
                        $pattern,
                        $source,
                        $matches,
                        PREG_OFFSET_CAPTURE
                    ) > 0
                ) {
                    foreach ($matches[0] as [$match, $offset]) {
                        $violations[] =
                            $this->relative($path)
                            . ':'
                            . $this->lineNumber(
                                $source,
                                $offset
                            )
                            . ' => '
                            . $match;
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Blocked style API tersisa:\n"
                . implode("\n", $violations)
        );
    }

    public function test_external_frontend_contains_csp_safe_style_bridge_and_global_x_cloak(): void
    {
        $appJs = (string) file_get_contents(
            resource_path('js/app.js')
        );

        $appCss = (string) file_get_contents(
            resource_path('css/app.css')
        );

        $this->assertStringContainsString(
            'QualityHub CSP-safe data style bridge',
            $appJs
        );

        $this->assertStringContainsString(
            'element.style.width',
            $appJs
        );

        $this->assertStringContainsString(
            'MutationObserver',
            $appJs
        );

        $this->assertMatchesRegularExpression(
            '/\[x-cloak\]\s*\{\s*display\s*:\s*none\s*!important\s*;\s*\}/s',
            $appCss
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
            '/\.blade\.php$/i'
        );

        $files = [];

        foreach ($regex as $file) {
            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    /** @return array<int, string> */
    private function frontendSourceFiles(): array
    {
        $files = $this->bladeFiles();

        $jsRoot = resource_path('js');

        if (is_dir($jsRoot)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $jsRoot,
                    RecursiveDirectoryIterator::SKIP_DOTS
                )
            );

            foreach ($iterator as $file) {
                if (
                    $file->isFile()
                    && in_array(
                        strtolower($file->getExtension()),
                        ['js', 'ts'],
                        true
                    )
                ) {
                    $files[] = $file->getPathname();
                }
            }
        }

        sort($files);

        return array_values(
            array_unique($files)
        );
    }

    private function lineNumber(
        string $source,
        int $offset
    ): int {
        return substr_count(
            substr($source, 0, $offset),
            "\n"
        ) + 1;
    }

    private function relative(string $path): string
    {
        return str_replace(
            '\\',
            '/',
            ltrim(
                str_replace(
                    base_path(),
                    '',
                    $path
                ),
                '\\/'
            )
        );
    }
}
