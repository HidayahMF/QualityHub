<?php

namespace Tests\Feature;

use App\Models\DocumentRetooling;
use Tests\TestCase;

class RetoolingCycleTimeMinutesUiTest extends TestCase
{
    public function test_scan_type_options_and_selected_cycle_times_show_minutes(): void
    {
        $view = file_get_contents(resource_path('views/documents/scan.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('CT ${product.cycle_time} menit', $view);
        $this->assertSame(2, substr_count($view, 'Cycle Time: ${product.cycle_time} menit'));
        $this->assertStringNotContainsString('Cycle Time: ${product.cycle_time} detik', $view);
    }

    public function test_retooling_detail_uses_minutes_for_current_and_historical_snapshots(): void
    {
        $view = file_get_contents(resource_path('views/documents/show.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString("' menit'", $view);
        $this->assertStringContainsString("'value' => \$cycleTimeMinutes(\$fromCycle)", $view);
        $this->assertStringContainsString("'value' => \$cycleTimeMinutes(\$toCycle)", $view);
        $this->assertStringContainsString(
            '$fromCycleSnapshot ?? $productCycle($scanMetadata?->from_type)',
            $view
        );
        $this->assertStringContainsString(
            '$toCycleSnapshot ?? $productCycle($scanMetadata?->to_type)',
            $view
        );
    }

    public function test_cycle_time_storage_remains_decimal_without_unit_conversion(): void
    {
        $casts = (new DocumentRetooling())->getCasts();

        $this->assertSame('decimal:2', $casts['from_cycle_time']);
        $this->assertSame('decimal:2', $casts['to_cycle_time']);

        $controller = file_get_contents(app_path('Http/Controllers/ScanDocumentController.php'));

        $this->assertIsString($controller);
        $this->assertStringContainsString("'CT as cycle_time'", $controller);
        $this->assertStringContainsString(
            "'from_cycle_time' => \$fromLineProduct->cycle_time",
            $controller
        );
        $this->assertStringContainsString(
            "'to_cycle_time' => \$toLineProduct->cycle_time",
            $controller
        );
    }
}
