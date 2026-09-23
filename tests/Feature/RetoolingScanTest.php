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
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_scan_view_uses_canonical_retooling_master_fields(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('documents.scan.create'))
            ->assertOk()
            ->baseResponse
            ->getContent();

        $this->assertStringContainsString(
            "const scanTime = document.getElementById('scan_time');",
            $html
        );
        $this->assertStringContainsString('id="retoolingLineField"', $html);
        $this->assertStringContainsString('id="retoolingFromSearch"', $html);
        $this->assertStringContainsString('id="retoolingToSearch"', $html);
        $this->assertStringContainsString('id="retoolingMasterTypeFields"', $html);
        $this->assertMatchesRegularExpression(
            '/id="scan_time"\s+type="text"/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/id="retoolingTimeOptions"[^>]*role="listbox"/',
            $html
        );
        $this->assertStringContainsString(
            "scanTime.setAttribute('role', 'combobox');",
            $html
        );
        $this->assertMatchesRegularExpression(
            "/scanTime\\.setAttribute\\(\\s*'aria-controls',\\s*'retoolingTimeOptions'\\s*\\)/",
            $html
        );
        $this->assertStringContainsString(
            'function renderTimeSuggestions(',
            $html
        );
        $this->assertStringContainsString(
            'function isTimeAllowedForShift(',
            $html
        );
        $this->assertStringContainsString(
            'function timeSuggestionsForShift(',
            $html
        );
        $this->assertStringContainsString(
            'Jam Shift ${shift.value}: ${range.label}',
            $html
        );
        $this->assertStringContainsString(
            '() => syncTimeMode(true)',
            $html
        );
        $this->assertStringContainsString(
            'function availableProductsFor(searchInput)',
            $html
        );
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

        $this->assertSame('INSTRUKSI PERUBAHAN TYPE (RETOOLING)', $document->title);
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

    public function test_retooling_rejects_manipulated_same_casting_without_artifacts(): void
    {
        $master = $this->createMaster();
        $payload = $this->retoolingPayload($master);
        $payload['to_casting_id'] = (string) $payload['from_casting_id'];

        $this->actingAs($this->admin)
            ->post(route('documents.scan.store'), $payload)
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasErrors([
                'to_casting_id' => 'Dari Type dan Ke Type tidak boleh sama.',
            ]);

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_retoolings', 0);
        $this->assertDatabaseCount('document_scan_metadata', 0);
        $this->assertDatabaseCount('document_versions', 0);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertSame([], Storage::disk('bmc_documents')->allFiles());
    }

    public function test_retooling_accepts_different_castings_on_selected_line(): void
    {
        $master = $this->createMaster();
        $productB = Casting::create([
            'code' => 'product_B',
            'name' => 'Type B',
            'is_active' => true,
        ]);

        LineProduct::query()
            ->where('production_line_id', $master['line1']->id)
            ->where('casting_id', $master['a']->id)
            ->update(['cycle_time' => 20]);

        LineProduct::create([
            'production_line_id' => $master['line1']->id,
            'casting_id' => $productB->id,
            'cycle_time' => 24,
            'is_active' => true,
        ]);

        $payload = $this->retoolingPayload($master);
        $payload['production_line_id'] = $master['line1']->id;
        $payload['from_casting_id'] = $master['a']->id;
        $payload['to_casting_id'] = $productB->id;

        $this->actingAs($this->admin)
            ->post(route('documents.scan.store'), $payload)
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasNoErrors();

        $retooling = DocumentRetooling::query()->sole();
        $this->assertSame($master['line1']->id, $retooling->production_line_id);
        $this->assertSame($master['a']->id, $retooling->from_casting_id);
        $this->assertSame($productB->id, $retooling->to_casting_id);
        $this->assertSame('20.00', $retooling->from_cycle_time);
        $this->assertSame('24.00', $retooling->to_cycle_time);
    }

    public function test_retooling_accepts_typed_time_in_hh_mm_format(): void
    {
        $master = $this->createMaster();

        $this->actingAs($this->admin)
            ->post(route('documents.scan.store'), array_merge(
                $this->retoolingPayload($master),
                ['scan_time' => '08:05']
            ))
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $scan = DocumentScanMetadata::query()->sole();
        $version = DocumentVersion::query()->sole();

        $this->assertSame('08:05', substr((string) $scan->scan_time, 0, 5));
        $this->assertSame(
            '08:05',
            $version->metadata_snapshot['scan_metadata']['scan_time']
        );
        $this->assertDatabaseCount('documents', 1);
        $this->assertDatabaseCount('document_retoolings', 1);
    }

    #[DataProvider('retoolingShiftTimeBoundaryProvider')]
    public function test_retooling_enforces_shift_time_half_open_ranges(
        string $shift,
        string $time,
        bool $isValid,
        string $rangeLabel
    ): void {
        $master = $this->createMaster();

        $response = $this->actingAs($this->admin)
            ->post(route('documents.scan.store'), array_merge(
                $this->retoolingPayload($master),
                [
                    'shift' => $shift,
                    'scan_time' => $time,
                ]
            ))
            ->assertRedirect(route('documents.scan.create'));

        if ($isValid) {
            $response
                ->assertSessionHasNoErrors()
                ->assertSessionHas('success');

            $this->assertDatabaseCount('documents', 1);
            $this->assertDatabaseCount('document_scan_metadata', 1);
            $this->assertDatabaseCount('document_retoolings', 1);
            $this->assertDatabaseCount('document_versions', 1);
            $this->assertDatabaseCount('activity_logs', 1);
            $this->assertCount(1, Storage::disk('bmc_documents')->allFiles());

            $metadata = DocumentScanMetadata::query()->sole();
            $this->assertSame($shift, (string) $metadata->shift);
            $this->assertSame($time, substr((string) $metadata->scan_time, 0, 5));

            return;
        }

        $response->assertSessionHasErrors([
            'scan_time' => "Jam {$time} tidak sesuai dengan Shift {$shift} ({$rangeLabel}).",
        ]);

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_scan_metadata', 0);
        $this->assertDatabaseCount('document_retoolings', 0);
        $this->assertDatabaseCount('document_versions', 0);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertSame([], Storage::disk('bmc_documents')->allFiles());
    }

    public static function retoolingShiftTimeBoundaryProvider(): array
    {
        return [
            'shift 1 starts at 07:00' => ['1', '07:00', true, '07:00–15:59'],
            'shift 1 accepts 08:30' => ['1', '08:30', true, '07:00–15:59'],
            'shift 1 ends at 15:59' => ['1', '15:59', true, '07:00–15:59'],
            'shift 1 rejects 06:59' => ['1', '06:59', false, '07:00–15:59'],
            'shift 1 excludes 16:00' => ['1', '16:00', false, '07:00–15:59'],
            'shift 2 starts at 16:00' => ['2', '16:00', true, '16:00–22:59'],
            'shift 2 accepts 18:00' => ['2', '18:00', true, '16:00–22:59'],
            'shift 2 ends at 22:59' => ['2', '22:59', true, '16:00–22:59'],
            'shift 2 rejects 15:59' => ['2', '15:59', false, '16:00–22:59'],
            'shift 2 excludes 23:00' => ['2', '23:00', false, '16:00–22:59'],
            'shift 3 starts at 23:00' => ['3', '23:00', true, '23:00–06:59'],
            'shift 3 accepts 23:59' => ['3', '23:59', true, '23:00–06:59'],
            'shift 3 accepts midnight' => ['3', '00:00', true, '23:00–06:59'],
            'shift 3 accepts 00:30' => ['3', '00:30', true, '23:00–06:59'],
            'shift 3 ends at 06:59' => ['3', '06:59', true, '23:00–06:59'],
            'shift 3 excludes 07:00' => ['3', '07:00', false, '23:00–06:59'],
            'shift 3 rejects 22:59' => ['3', '22:59', false, '23:00–06:59'],
        ];
    }

    public function test_retooling_rejects_manipulated_shift_time_without_artifacts(): void
    {
        $master = $this->createMaster();

        $this->actingAs($this->admin)
            ->post(route('documents.scan.store'), array_merge(
                $this->retoolingPayload($master),
                ['scan_time' => '20:00']
            ))
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasErrors([
                'scan_time' => 'Jam 20:00 tidak sesuai dengan Shift 1 (07:00–15:59).',
            ])
            ->assertSessionHasInput('shift', '1')
            ->assertSessionHasInput('scan_time', '20:00');

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_scan_metadata', 0);
        $this->assertDatabaseCount('document_retoolings', 0);
        $this->assertDatabaseCount('document_versions', 0);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertSame([], Storage::disk('bmc_documents')->allFiles());
    }

    public function test_retooling_rejects_invalid_typed_time_without_records(): void
    {
        $master = $this->createMaster();

        $this->actingAs($this->admin)
            ->post(route('documents.scan.store'), array_merge(
                $this->retoolingPayload($master),
                ['scan_time' => '25:00']
            ))
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasErrors('scan_time')
            ->assertSessionHasInput('scan_time', '25:00');

        $response = $this->get(route('documents.scan.create'))
            ->assertOk();

        $html = $response->baseResponse->getContent();
        $this->assertMatchesRegularExpression(
            '/id="scan_time"\s+type="text"[^>]+value="25:00"/',
            $html
        );

        foreach (['7:00', '12:99', 'abc', '8 pagi'] as $invalidTime) {
            $this->post(route('documents.scan.store'), array_merge(
                $this->retoolingPayload($master),
                ['scan_time' => $invalidTime]
            ))
                ->assertRedirect(route('documents.scan.create'))
                ->assertSessionHasErrors('scan_time');
        }

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_retoolings', 0);
        $this->assertDatabaseCount('document_scan_metadata', 0);
        $this->assertDatabaseCount('document_versions', 0);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertSame([], Storage::disk('bmc_documents')->allFiles());
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

    public function test_retooling_document_detail_shows_historical_snapshot_card(): void
    {
        $master = $this->createMaster();

        $this->actingAs($this->admin)
            ->post(route('documents.scan.store'), array_merge($this->retoolingPayload($master), [
                'scan_time' => '08:30',
            ]))
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasNoErrors();

        $document = Document::query()->sole();

        $this->get(route('documents.show', $document))
            ->assertOk()
            ->assertSee('Informasi Retooling')
            ->assertSee('Shift 1')
            ->assertSee('LINE_2')
            ->assertSee('product_A')
            ->assertSee('product_E')
            ->assertSee('25.00')
            ->assertSee('22.00')
            ->assertSee('08:30');
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

    public function test_axle_and_brake_scans_require_database_master_ids(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('documents.scan.store'), array_merge($this->basePayload(), [
            'scan_type' => 'Laporan Hasil Produksi - Axle Assy',
            'axle_assy_type' => 'Module Assy',
            'axle_side' => 'LH',
        ]))
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasErrors([
                'axle_type_id',
                'axle_position_id',
            ]);

        $this->post(route('documents.scan.store'), array_merge($this->basePayload(), [
            'scan_type' => 'Laporan Hasil Produksi - Brake Assy',
            'brake_part_variant' => '402 / 502',
        ]))
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasErrors('brake_part_number_id');

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_retoolings', 0);
        $this->assertSame([], Storage::disk('bmc_documents')->allFiles());
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
