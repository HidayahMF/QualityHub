<?php

namespace Tests\Feature;

use Tests\TestCase;

class AllDocumentsMinimalUiTest extends TestCase
{
    private function viewSource(): string
    {
        $source = file_get_contents(resource_path('views/documents/index.blade.php'));
        $this->assertIsString($source);

        return $source;
    }

    public function test_compact_header_and_all_document_actions(): void
    {
        $view = $this->viewSource();
        $this->assertStringContainsString('data-qh-documents-header', $view);
        $this->assertStringContainsString('All Documents', $view);
        $this->assertStringContainsString("route('favorites.index')", $view);
        $this->assertStringContainsString("route('documents.create')", $view);
        foreach (['Sumber Data Saat Ini', 'Pengembangan Lokal',
            'Integrasi server BMC akan dikonfigurasi kemudian', 'Penyimpanan Dokumen'] as $obsolete) {
            $this->assertStringNotContainsString($obsolete, $view);
        }
    }

    public function test_primary_search_precedes_collapsed_ai_search_and_document_library(): void
    {
        $view = $this->viewSource();
        $primary = strpos($view, 'x-data="documentSearch"');
        $ai = strpos($view, 'x-data="aiSmartSearch"');
        $library = strpos($view, 'Pustaka Dokumen');
        $this->assertNotFalse($primary);
        $this->assertNotFalse($ai);
        $this->assertNotFalse($library);
        $this->assertLessThan($ai, $primary);
        $this->assertLessThan($library, $ai);
        $this->assertStringContainsString('data-qh-ai-search', $view);
        $this->assertStringContainsString('@submit.prevent="performAiSearch()"', $view);
        $this->assertStringContainsString('id="ai-smart-search-results"', $view);
    }

    public function test_filter_search_and_qc_shortcuts_preserve_their_handlers(): void
    {
        $view = $this->viewSource();
        foreach (['search','department','category','extension','documentType','dateFrom','dateTo'] as $name) {
            $this->assertStringContainsString('x-model="'.$name.'"', $view);
        }
        foreach (['@input.debounce.180ms="performSearch()"',
            '@change="performSearch()"','submitFilters()','resetSearch()',
            "quickType('Retooling')","quickSearch('LH')","quickSearch('RH')",
            "quickSearch('402 / 502')"] as $action) {
            $this->assertStringContainsString($action, $view);
        }
        $this->assertSame(4, substr_count($view, '<select data-qh-searchable-select'));
        $this->assertSame(4, preg_match_all('/<select\b/', $view));
        $this->assertStringContainsString('data-qh-documents-advanced', $view);
    }

    public function test_document_list_and_live_results_stay_present(): void
    {
        $view = $this->viewSource();
        $this->assertStringContainsString('@foreach($documents as $document)', $view);
        $this->assertStringContainsString('Pustaka Dokumen', $view);
        $this->assertStringContainsString('x-for="document in results"', $view);
        $this->assertStringContainsString('id="document-search"', $view);
    }
}
