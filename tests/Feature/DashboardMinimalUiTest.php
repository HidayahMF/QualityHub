<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardMinimalUiTest extends TestCase
{
    private function dashboardView(): string
    {
        $source = file_get_contents(resource_path('views/dashboard.blade.php'));
        $this->assertIsString($source);
        return $source;
    }

    public function test_compact_header_retains_search_and_period_controls(): void
    {
        $view = $this->dashboardView();
        $this->assertStringContainsString('data-qh-dashboard-header', $view);
        $this->assertStringContainsString('id="dashboard-period"', $view);
        $this->assertStringContainsString('name="period"', $view);
        $this->assertStringContainsString('data-auto-submit', $view);
        $this->assertStringContainsString('name="search"', $view);
        $this->assertStringContainsString("route('documents.index')", $view);
        $this->assertStringContainsString('$periodDateLabel', $view);
        $this->assertStringNotContainsString('bg-gradient-to-br from-blue-950 via-blue-900 to-blue-700', $view);
    }

    public function test_all_nine_kpis_and_period_context_are_retained(): void
    {
        $view = $this->dashboardView();
        foreach ([
            '$totalDocuments', '$archivedDocuments', '$totalRevisions',
            '$totalDepartments', '$totalCategories', '$activeUsers',
            '$pendingUsers', '$activityInPeriod', '$spreadsheetDocuments',
            '$totalStorageBytes', '$newDocumentsInPeriod', '$growthLabel',
        ] as $name) {
            $this->assertStringContainsString($name, $view);
        }
        $this->assertStringContainsString('data-qh-dashboard-kpi', $view);
    }

    public function test_existing_svg_chart_targets_and_data_are_unchanged(): void
    {
        $view = $this->dashboardView();
        foreach ([
            'dashboard-chart-data', 'trend-chart',
            'category-donut-chart', 'category-donut-legend',
            'filetype-donut-chart', 'filetype-donut-legend',
            'department-bar-chart', 'activity-bar-chart',
            'data-qh-dashboard-trend', 'data-qh-dashboard-distributions',
        ] as $id) {
            $this->assertStringContainsString($id, $view);
        }
        $this->assertStringContainsString('@json($chartData)', $view);
        $this->assertStringContainsString('nonce="{{ $cspNonce }}"', $view);
    }

    public function test_recent_document_and_activity_lists_keep_existing_sources(): void
    {
        $view = $this->dashboardView();
        $this->assertStringContainsString('@forelse($topAccessedDocuments as $index => $document)', $view);
        $this->assertStringContainsString('@forelse($recentDocuments as $document)', $view);
        $this->assertStringContainsString('@forelse($recentActivities as $activity)', $view);
        $this->assertStringContainsString("route('documents.show', $document", $view);
        $this->assertStringContainsString('data-qh-dashboard-documents', $view);
        $this->assertStringContainsString('data-qh-dashboard-activities', $view);
    }
}
