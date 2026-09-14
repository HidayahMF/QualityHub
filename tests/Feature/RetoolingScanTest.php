<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Casting;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentRetooling;
use App\Models\DocumentScanMetadata;
use App\Models\DocumentVersion;
use App\Models\LineProduct;
use App\Models\ProductionLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RetoolingScanTest extends TestCase
{
    use RefreshDatabase;

    private string $storageRoot;

    private Category $category;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageRoot = storage_path(
            'framework/testing/retooling-scan-' . bin2hex(random_bytes(8))
        );

        File::ensureDirectoryExists($this->storageRoot);

        config([
            'filesystems.disks.bmc_documents' => [
                'driver' => 'local',
                'root' => $this->storageRoot,
                'throw' => false,
            ],
        ]);

        Storage::purge('bmc_documents');

        $department = Department::create([
            'name' => 'Quality Control',
            'code' => 'QC',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'department_id' => $department->id,
            'name' => 'QC Scans',
            'slug' => 'qc-scans',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Storage::purge('bmc_documents');

        if (isset($this->storageRoot)) {
            File::deleteDirectory($this->storageRoot);
        }

        parent::tearDown();
    }

    public function test_retooling_post_saves_master_snapshot_and_ignores_browser_supplied_values(): void
    {
        $master = $this->createMaster();

        $this->actingAs($this->admin)
            ->post(route('documents.scan.store'), array_merge(
                $this->retoolingPayload($master),
                [
                    'line' => 'FORGED LINE',
                    'from_type' => 'FORGED FROM',
                    'to_type' => 'FORGED TO',
                    'from_cycle_time' => '999.99',
                    'to_cycle_time' => '0.01',
                ]
            ))
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $document = Document::query()->sole();
        $scan = DocumentScanMetadata::query()->sole();
        $retooling = DocumentRetooling::query()->sole();
        $version = DocumentVersion::query()->sole();
        $activity = ActivityLog::query()->sole();

        $this->assertSame('INSTRUKSI PERUBAHAN TYPE ( RETOOLING )', $document->title);
        $this->assertSame($this->admin->id, $document->uploaded_by);
        $this->assertSame('png', $document->file_extension);
        $this->assertTrue(Storage::disk('bmc_documents')->exists($document->file_path));

        $this->assertSame($document->id, $scan->document_id);
        $this->assertSame('Retooling', $scan->scan_type);
        $this->assertSame('1', (string) $scan->shift);
        $this->assertSame('LINE_2', $scan->line);
        $this->assertSame('product_A', $scan->from_type);
        $this->assertSame('product_E', $scan->to_type);

        $this->assertSame($document->id, $retooling->document_id);
        $this->assertSame(1, $retooling->shift);
        $this->assertSame($master['line2']->id, $retooling->production_line_id);
        $this->assertSame($master['a']->id, $retooling->from_casting_id);
        $this->assertSame($master['e']->id, $retooling->to_casting_id);
        $this->assertSame('25.00', $retooling->from_cycle_time);
        $this->assertSame('22.00', $retooling->to_cycle_time);

        $this->assertSame($document->id, $version->document_id);
        $this->assertSame(0, $version->revision_number);
        $this->assertSame($document->file_path, $version->file_path);
        $this->assertSame('Retooling', $version->metadata_snapshot['scan_metadata']['scan_type']);
        $this->assertSame('LINE_2', $version->metadata_snapshot['scan_metadata']['line']);
        $this->assertSame('product_A', $version->metadata_snapshot['scan_metadata']['from_type']);
        $this->assertSame('product_E', $version->metadata_snapshot['scan_metadata']['to_type']);

        $this->assertSame('scan_document', $activity->action);
        $this->assertSame($document->id, $activity->document_id);
        $this->assertSame($this->admin->id, $activity->user_id);

        $master['line2a']->update(['cycle_time' => 40]);
        $master['line2e']->update(['cycle_time' => 41]);
        $this->assertSame('25.00', $retooling->fresh()->from_cycle_time);
        $this->assertSame('22.00', $retooling->fresh()->to_cycle_time);
    }

    public function test_retooling_rejects_missing_empty_and_invalid_shift(): void
    {
        $master = $this->createMaster();
        $this->actingAs($this->admin);

        foreach (['missing', '', '4'] as $shift) {
            $payload = $this->retoolingPayload($master);

            if ($shift === 'missing') {
                unset($payload['shift']);
            } else {
                $payload['shift'] = $shift;
            }

            $this->post(route('documents.scan.store'), $payload)
                ->assertRedirect(route('documents.scan.create'))
                ->assertSessionHasErrors('shift');
        }

        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], Storage::disk('bmc_documents')->allFiles());
    }

    public function test_retooling_rejects_casting_that_is_not_on_selected_line_and_removes_uploaded_file(): void
    {
        $master = $this->createMaster();
        $payload = $this->retoolingPayload($master);
        $payload['from_casting_id'] = $master['c']->id;

        $this->actingAs($this->admin)
            ->post(route('documents.scan.store'), $payload)
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasErrors('from_casting_id');

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_retoolings', 0);
        $this->assertSame([], Storage::disk('bmc_documents')->allFiles());
    }

    public function test_retooling_rejects_inactive_line_casting_and_line_product(): void
    {
        $master = $this->createMaster();
        $this->actingAs($this->admin);

        $master['line2']->update(['is_active' => false]);
        $this->post(route('documents.scan.store'), $this->retoolingPayload($master))
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasErrors('production_line_id');
        $master['line2']->update(['is_active' => true]);

        $master['e']->update(['is_active' => false]);
        $this->post(route('documents.scan.store'), $this->retoolingPayload($master))
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasErrors('to_casting_id');
        $master['e']->update(['is_active' => true]);

        $master['line2e']->update(['is_active' => false]);
        $this->post(route('documents.scan.store'), $this->retoolingPayload($master))
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasErrors('to_casting_id');

        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], Storage::disk('bmc_documents')->allFiles());
    }

    public function test_master_endpoints_show_only_active_data_with_line_specific_cycle_times(): void
    {
        $master = $this->createMaster();
        $inactiveLine = ProductionLine::create([
            'code' => 'LINE_HIDDEN', 'name' => 'Hidden', 'is_active' => false,
        ]);
        $inactiveCasting = Casting::create([
            'code' => 'HIDDEN', 'name' => 'Hidden', 'is_active' => false,
        ]);
        LineProduct::create([
            'production_line_id' => $master['line2']->id,
            'casting_id' => $inactiveCasting->id,
            'cycle_time' => 11,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('documents.scan.master.lines'))
            ->assertOk()
            ->assertJsonCount(2, 'results')
            ->assertJsonPath('results.0.code', 'LINE_1')
            ->assertJsonPath('results.1.code', 'LINE_2');

        $this->get(route('documents.scan.master.lines', ['q' => 'Line 2']))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.id', $master['line2']->id);

        $this->get(route('documents.scan.master.products', $master['line2']))
            ->assertOk()
            ->assertJsonCount(2, 'results')
            ->assertJsonPath('line.code', 'LINE_2')
            ->assertJsonPath('results.0.code', 'product_A')
            ->assertJsonPath('results.0.cycle_time', '25')
            ->assertJsonPath('results.1.code', 'product_E')
            ->assertJsonPath('results.1.cycle_time', '22');

        $this->get(route('documents.scan.master.products', $master['line1']))
            ->assertOk()
            ->assertJsonCount(2, 'results')
            ->assertJsonPath('results.0.code', 'product_A')
            ->assertJsonPath('results.0.cycle_time', '30')
            ->assertJsonPath('results.1.code', 'product_C');

        $this->get(route('documents.scan.master.products', $inactiveLine))
            ->assertNotFound();
    }

    public function test_master_endpoints_require_an_active_admin(): void
    {
        $master = $this->createMaster();
        $lines = route('documents.scan.master.lines');
        $products = route('documents.scan.master.products', $master['line2']);

        $this->get($lines)->assertRedirect(route('login'));
        $this->get($products)->assertRedirect(route('login'));

        $user = User::factory()->create(['role' => 'user', 'account_status' => 'active']);
        $this->actingAs($user);
        $this->get($lines)->assertForbidden();
        $this->get($products)->assertForbidden();
    }

    public function test_failed_scan_returns_to_form_even_if_previous_url_was_master_json(): void
    {
        $master = $this->createMaster();
        $payload = $this->retoolingPayload($master);
        $payload['shift'] = '';

        $this->actingAs($this->admin)
            ->from(route('documents.scan.master.products', $master['line2']))
            ->post(route('documents.scan.store'), $payload)
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasErrors('shift');
    }

    public function test_inactive_department_error_returns_to_form_from_master_json_referer(): void
    {
        $master = $this->createMaster();
        $this->category->department->update(['is_active' => false]);

        $this->actingAs($this->admin)
            ->from(route('documents.scan.master.products', $master['line2']))
            ->post(route('documents.scan.store'), $this->retoolingPayload($master))
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_unusable_file_name_returns_to_form_from_master_json_referer(): void
    {
        $master = $this->createMaster();
        $payload = array_merge($this->basePayload(), [
            'scan_type' => 'Lainnya',
            'scan_type_other' => 'Custom QC',
            'file_name' => '💡',
        ]);

        $this->actingAs($this->admin)
            ->from(route('documents.scan.master.products', $master['line2']))
            ->post(route('documents.scan.store'), $payload)
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasErrors('file_name');

        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], Storage::disk('bmc_documents')->allFiles());
    }

    public function test_storage_failure_returns_to_form_from_master_json_referer_without_database_records(): void
    {
        $master = $this->createMaster();
        $blockedRoot = $this->storageRoot . DIRECTORY_SEPARATOR . 'blocked-root';
        File::put($blockedRoot, 'This file cannot be used as a storage directory.');
        config(['filesystems.disks.bmc_documents.root' => $blockedRoot]);
        Storage::purge('bmc_documents');

        $this->actingAs($this->admin)
            ->from(route('documents.scan.master.products', $master['line2']))
            ->post(route('documents.scan.store'), $this->retoolingPayload($master))
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasErrors('files');

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_retoolings', 0);
    }

    public function test_axle_and_brake_scans_remain_compatible_without_retooling_master_selection(): void
    {
        $this->actingAs($this->admin);

        $base = $this->basePayload();
        $this->post(route('documents.scan.store'), array_merge($base, [
            'scan_type' => 'Laporan Hasil Produksi - Axle Assy',
            'axle_assy_type' => 'Module Assy',
            'axle_side' => 'LH',
        ]))
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasNoErrors();

        $this->post(route('documents.scan.store'), array_merge($this->basePayload(), [
            'scan_type' => 'Laporan Hasil Produksi - Brake Assy',
            'brake_part_variant' => '402 / 502',
        ]))
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('documents', 2);
        $this->assertDatabaseCount('document_retoolings', 0);
        $this->assertDatabaseHas('document_scan_metadata', [
            'scan_type' => 'Laporan Hasil Produksi - Axle Assy',
            'axle_assy_type' => 'Module Assy',
            'axle_side' => 'LH',
        ]);
        $this->assertDatabaseHas('document_scan_metadata', [
            'scan_type' => 'Laporan Hasil Produksi - Brake Assy',
            'brake_part_number' => 'A 4304200 402 / 502',
        ]);
    }

    private function createMaster(): array
    {
        $line1 = ProductionLine::create([
            'code' => 'LINE_1', 'name' => 'Line 1', 'is_active' => true,
        ]);
        $line2 = ProductionLine::create([
            'code' => 'LINE_2', 'name' => 'Line 2', 'is_active' => true,
        ]);
        $a = Casting::create(['code' => 'product_A', 'name' => 'Type A', 'is_active' => true]);
        $e = Casting::create(['code' => 'product_E', 'name' => 'Type E', 'is_active' => true]);
        $c = Casting::create(['code' => 'product_C', 'name' => 'Type C', 'is_active' => true]);

        LineProduct::create([
            'production_line_id' => $line1->id,
            'casting_id' => $a->id,
            'cycle_time' => 30,
            'is_active' => true,
        ]);
        LineProduct::create([
            'production_line_id' => $line1->id,
            'casting_id' => $c->id,
            'cycle_time' => 27,
            'is_active' => true,
        ]);
        $line2a = LineProduct::create([
            'production_line_id' => $line2->id,
            'casting_id' => $a->id,
            'cycle_time' => 25,
            'is_active' => true,
        ]);
        $line2e = LineProduct::create([
            'production_line_id' => $line2->id,
            'casting_id' => $e->id,
            'cycle_time' => 22,
            'is_active' => true,
        ]);

        return compact('line1', 'line2', 'a', 'e', 'c', 'line2a', 'line2e');
    }

    private function retoolingPayload(array $master): array
    {
        return array_merge($this->basePayload(), [
            'scan_type' => 'Retooling',
            'shift' => '1',
            'production_line_id' => $master['line2']->id,
            'from_casting_id' => $master['a']->id,
            'to_casting_id' => $master['e']->id,
        ]);
    }

    private function basePayload(): array
    {
        return [
            'category_id' => $this->category->id,
            'title' => 'Scanned QC Document',
            'file_name' => 'qc-photo',
            'scan_date' => '2026-09-14',
            'capture_source' => 'upload',
            'files' => [UploadedFile::fake()->image('scan.png', 2, 2)],
        ];
    }
}
