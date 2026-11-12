<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SearchableFilterSelectTest extends TestCase
{
    /**
     * All filter dropdowns in the screens requested by QC must be enhanced.
     * View-source tests do not require a connection to external MTC/PPIC.
     *
     * @return array<string, array{string, int}>
     */
    public static function filterViews(): array
    {
        return [
            'documents' => ['documents/index.blade.php', 4],
            'archived documents' => ['documents/archived.blade.php', 2],
            'categories' => ['categories/index.blade.php', 2],
            'favorites' => ['favorites/index.blade.php', 3],
            'activity logs' => ['activity-logs/index.blade.php', 3],
            'departments' => ['departments/index.blade.php', 1],
            'users' => ['users/index.blade.php', 2],
        ];
    }

    #[DataProvider('filterViews')]
    public function test_filter_views_opt_in_all_select_fields(string $view, int $expected): void
    {
        $html = file_get_contents(resource_path('views/'.$view));

        $this->assertIsString($html);
        $this->assertSame(
            $expected,
            substr_count($html, '<select data-qh-searchable-select'),
            'Check searchable filter opt-in for '.$view
        );
        $this->assertSame(
            $expected,
            preg_match_all('/<select\\b/', $html),
            'Do not leave filter dropdowns unenhanced in '.$view
        );
    }

    public function test_global_assets_register_single_searchable_filter_component(): void
    {
        $appJs = file_get_contents(resource_path('js/app.js'));
        $module = file_get_contents(resource_path('js/searchable-filters.js'));
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString(
            "import { initializeSearchableFilters } from './searchable-filters';",
            $appJs
        );
        $this->assertStringContainsString('initializeSearchableFilters();', $appJs);
        $this->assertStringContainsString("select[data-qh-searchable-select]", $module);
        $this->assertStringContainsString("select.dispatchEvent(new Event('change', { bubbles: true }))", $module);
        $this->assertStringContainsString("select.dispatchEvent(new Event('input', { bubbles: true }))", $module);
        $this->assertStringContainsString('search.addEventListener(\'keydown\'', $module);
        $this->assertStringContainsString('MAX_VISIBLE_OPTIONS = 120', $module);
        $this->assertStringContainsString("document.body.append(popup)", $module);
        $this->assertStringContainsString('qh-searchable-popup[hidden]', $css);
        $this->assertStringContainsString('QUALITYHUB · SEARCHABLE FILTER SELECTS', $css);
    }

    public function test_old_form_field_names_and_live_document_search_are_preserved(): void
    {
        $archives = file_get_contents(resource_path('views/documents/archived.blade.php'));
        $favorites = file_get_contents(resource_path('views/favorites/index.blade.php'));
        $audits = file_get_contents(resource_path('views/activity-logs/index.blade.php'));
        $documents = file_get_contents(resource_path('views/documents/index.blade.php'));

        foreach (['department', 'category'] as $name) {
            $this->assertMatchesRegularExpression('/<select\\b[^>]*name="'.$name.'"/s', $archives);
            $this->assertMatchesRegularExpression('/<select\\b[^>]*name="'.$name.'"/s', $favorites);
        }

        foreach (['user_id', 'action', 'document_id'] as $name) {
            $this->assertMatchesRegularExpression('/<select\\b[^>]*name="'.$name.'"/s', $audits);
        }

        foreach (['department', 'category', 'extension', 'documentType'] as $model) {
            $this->assertStringContainsString('x-model="'.$model.'"', $documents);
        }
        $this->assertStringContainsString('@change="performSearch()"', $documents);
    }

    public function test_component_does_not_require_an_external_cdn_or_jquery(): void
    {
        $module = file_get_contents(resource_path('js/searchable-filters.js'));

        $this->assertStringNotContainsString('import jQuery', $module);
        $this->assertStringNotContainsString('https://', $module);
        $this->assertStringContainsString('select.tabIndex = -1', $module);
        $this->assertStringContainsString('const timer = window.setInterval', $module);
    }
}
