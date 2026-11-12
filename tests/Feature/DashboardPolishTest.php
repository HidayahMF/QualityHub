<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardPolishTest extends TestCase
{
    private function source(): string
    {
        $source = file_get_contents(resource_path('views/dashboard.blade.php'));
        $this->assertIsString($source);

        return $source;
    }

    public function test_bmc_welcome_uses_blue_background_with_accessible_search(): void
    {
        $source = $this->source();

        $this->assertStringContainsString('data-qh-dashboard-header', $source);
        $this->assertStringContainsString('from-blue-950 via-blue-900 to-blue-700', $source);
        $this->assertStringContainsString('text-white', $source);
        $this->assertStringContainsString('Selamat datang, {{ Auth::user()->name }}', $source);
        $this->assertStringContainsString('id="dashboard-period"', $source);
        $this->assertStringContainsString('data-auto-submit', $source);
        $this->assertStringContainsString("route('documents.index')", $source);
        $this->assertStringContainsString('name="search"', $source);
    }

    public function test_all_nine_kpis_share_one_grid_and_preserve_values(): void
    {
        $source = $this->source();

        $matchCount = preg_match(
            '/<section\b[^>]*data-qh-dashboard-kpi[^>]*>[\s\S]*?<\/section>/',
            $source,
            $match
        );

        $this->assertSame(1, $matchCount);
        $this->assertSame(9, substr_count($match[0], '<article'));

        foreach ([
            '$totalDocuments', '$archivedDocuments', '$totalRevisions',
            '$totalDepartments', '$activeUsers', '$activityInPeriod',
            '$spreadsheetDocuments', '$totalStorageBytes',
            '$newDocumentsInPeriod', '$growthLabel',
        ] as $value) {
            $this->assertStringContainsString($value, $match[0]);
        }
    }

    public function test_analytics_and_document_panels_are_balanced_without_old_span(): void
    {
        $source = $this->source();

        $this->assertStringContainsString('data-qh-dashboard-distributions', $source);
        $this->assertSame(2, substr_count($source, 'data-qh-dashboard-documents'));
        $this->assertStringNotContainsString('xl:col-span-2', $source);
        $this->assertStringContainsString('sm:grid-cols-[0.9fr_1.1fr]', $source);

        foreach ([
            'trend-chart', 'category-donut-chart', 'category-donut-legend',
            'filetype-donut-chart', 'filetype-donut-legend',
            'department-bar-chart', 'activity-bar-chart',
        ] as $id) {
            $this->assertStringContainsString('id="'.$id.'"', $source);
        }
    }

    public function test_recent_activity_is_one_column_with_full_source_text_preserved(): void
    {
        $source = $this->source();
        $start = strpos($source, 'data-qh-dashboard-activities');

        $this->assertNotFalse($start);

        $end = strpos($source, '</section>', $start);
        $this->assertNotFalse($end);

        $segment = substr($source, $start, $end - $start);

        $this->assertStringNotContainsString('sm:grid-cols-2', $segment);
        $this->assertStringContainsString('line-clamp-2', $segment);
        $this->assertStringContainsString('@forelse($recentActivities as $activity)', $segment);
        $this->assertStringContainsString('{{ $activityDescription($activity) }}', $segment);
        $this->assertStringContainsString('diffForHumans()', $segment);
    }
}
