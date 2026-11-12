<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanThousandPageUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_page_includes_mobile_bulk_mode_progress_and_page_navigation(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);

        $html = $this->actingAs($admin)
            ->get(route('documents.scan.create'))
            ->assertOk()
            ->baseResponse
            ->getContent();

        $this->assertStringContainsString('Mode 1.000 Halaman (Android &amp; iPhone)', $html);
        $this->assertStringContainsString('id="scanLargeMode"', $html);
        $this->assertStringContainsString('id="scanBatchId"', $html);
        $this->assertStringContainsString('id="scanUploadProgress"', $html);
        $this->assertStringContainsString('id="scanUploadProgressBar"', $html);
        $this->assertStringContainsString('id="photoQueueNavigation"', $html);
        $this->assertStringContainsString('id="photoQueuePrevious"', $html);
        $this->assertStringContainsString('id="photoQueueNext"', $html);
        $this->assertStringContainsString('const MAX_PHOTOS = 1000', $html);
        $this->assertStringContainsString('const QUEUE_PAGE_SIZE = 25', $html);
        $this->assertStringContainsString('const BATCH_CHUNK_SIZE = 10', $html);
        $this->assertStringContainsString("selectedItems.slice(startIndex, startIndex + QUEUE_PAGE_SIZE)", $html);
        $this->assertStringContainsString('async function uploadScansInBatches()', $html);
        $this->assertStringContainsString('scanFilesInput.disabled = true', $html);
        $this->assertStringContainsString("|| isMobileCameraDevice", $html);
        $this->assertStringNotContainsString('Maks. 100 foto', $html);
    }

    public function test_batch_routes_are_named_and_server_limit_is_one_thousand(): void
    {
        $this->assertTrue(route('documents.scan.batches.start') !== '');
        $this->assertTrue(route('documents.scan.batches.upload') !== '');
        $this->assertTrue(route('documents.scan.batches.discard', ['batchId' => '00000000-0000-4000-8000-000000000000']) !== '');
        $this->assertSame(1000, (int) config('smartfind_scan_security.max_files'));
    }
}
