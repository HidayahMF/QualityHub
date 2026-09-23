<?php

namespace Tests\Feature;

use App\Models\AxlePosition;
use App\Models\AxleType;
use App\Models\BrakePartNumber;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentAxleProduction;
use App\Models\DocumentBrakeProduction;
use App\Models\DocumentRetooling;
use App\Models\DocumentScanMetadata;
use App\Models\ProductionLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AxleBrakeProductionScanTest extends TestCase
{
    use RefreshDatabase;

    private string $storageRoot;

    private Category $category;

    private User $admin;

    private AxleType $moduleType;

    private AxleType $axleType;

    private AxlePosition $leftPosition;

    private AxlePosition $rightPosition;

    private BrakePartNumber $brake402;

    private BrakePartNumber $brake201;

    private BrakePartNumber $brake202;

    private ProductionLine $productionLine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageRoot = storage_path(
            'framework/testing/axle-brake-scan-' . bin2hex(random_bytes(8))
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

        $this->moduleType = AxleType::create([
            'code' => 'MODULE_ASSY',
            'name' => 'Module Assy',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->axleType = AxleType::create([
            'code' => 'AXLE_ASSY',
            'name' => 'Axle Assy',
            'is_active' => true,
            'sort_order' => 20,
        ]);

        $this->leftPosition = AxlePosition::create([
            'code' => 'LH',
            'name' => 'LH',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->rightPosition = AxlePosition::create([
            'code' => 'RH',
            'name' => 'RH',
            'is_active' => true,
            'sort_order' => 20,
        ]);

        $this->brake402 = BrakePartNumber::create([
            'prefix' => 'A 4304200',
            'variant' => '402 / 502',
            'display_name' => 'A 4304200 402 / 502',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->brake201 = BrakePartNumber::create([
            'prefix' => 'A 4304200',
            'variant' => '201 / 301',
            'display_name' => 'A 4304200 201 / 301',
            'is_active' => true,
            'sort_order' => 20,
        ]);

        $this->brake202 = BrakePartNumber::create([
            'prefix' => 'A 4304200',
            'variant' => '202 / 302',
            'display_name' => 'A 4304200 202 / 302',
            'is_active' => true,
            'sort_order' => 30,
        ]);

        $this->productionLine = ProductionLine::create([
            'code' => 'LINE_AXLE_BRAKE',
            'name' => 'MOP 3',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Storage::purge('bmc_documents');

        if (isset($this->storageRoot)) {
            File::deleteDirectory($this->storageRoot);
            File::delete($this->storageRoot . '-blocked');
        }

        parent::tearDown();
    }

    public function test_axle_and_brake_master_endpoints_return_only_active_sorted_database_values(): void
    {
        AxleType::create([
            'code' => 'HIDDEN_TYPE',
            'name' => 'Hidden Type',
            'is_active' => false,
            'sort_order' => 1,
        ]);

        AxlePosition::create([
            'code' => 'HIDDEN_POSITION',
            'name' => 'Hidden Position',
            'is_active' => false,
            'sort_order' => 1,
        ]);

        BrakePartNumber::create([
            'prefix' => 'HIDDEN',
            'variant' => '999',
            'display_name' => 'HIDDEN 999',
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $this->actingAs($this->admin);

        $this->getJson(route('documents.scan.master.axle-types'))
            ->assertOk()
            ->assertJsonCount(2, 'results')
            ->assertJsonPath('results.0.id', $this->moduleType->id)
            ->assertJsonPath('results.0.text', 'Module Assy')
            ->assertJsonPath('results.0.code', 'MODULE_ASSY')
            ->assertJsonPath('results.1.id', $this->axleType->id)
            ->assertJsonMissing(['code' => 'HIDDEN_TYPE']);

        $this->getJson(route('documents.scan.master.axle-positions'))
            ->assertOk()
            ->assertJsonCount(2, 'results')
            ->assertJsonPath('results.0.id', $this->leftPosition->id)
            ->assertJsonPath('results.0.name', 'LH')
            ->assertJsonPath('results.1.id', $this->rightPosition->id)
            ->assertJsonMissing(['code' => 'HIDDEN_POSITION']);

        $this->getJson(route('documents.scan.master.brake-part-numbers'))
            ->assertOk()
            ->assertJsonCount(3, 'results')
            ->assertJsonPath('results.0.id', $this->brake402->id)
            ->assertJsonPath('results.0.prefix', 'A 4304200')
            ->assertJsonPath('results.0.variant', '402 / 502')
            ->assertJsonPath('results.0.display_name', 'A 4304200 402 / 502')
            ->assertJsonPath('results.1.id', $this->brake201->id)
            ->assertJsonPath('results.2.id', $this->brake202->id)
            ->assertJsonMissing(['variant' => '999']);
    }

    public function test_brake_master_endpoint_searches_prefix_variant_and_display_name(): void
    {
        $this->actingAs($this->admin);

        $this->getJson(route(
            'documents.scan.master.brake-part-numbers',
            ['q' => '201 / 301']
        ))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.id', $this->brake201->id);

        $this->getJson(route(
            'documents.scan.master.brake-part-numbers',
            ['q' => 'A 4304200 202']
        ))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.id', $this->brake202->id);

        $this->getJson(route(
            'documents.scan.master.brake-part-numbers',
            ['q' => 'not available']
        ))
            ->assertOk()
            ->assertJsonCount(0, 'results');
    }

    public function test_production_master_endpoints_require_an_active_admin(): void
    {
        $routes = [
            route('documents.scan.master.axle-types'),
            route('documents.scan.master.axle-positions'),
            route('documents.scan.master.brake-part-numbers'),
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertRedirect(route('login'));
        }

        $user = User::factory()->create([
            'role' => 'user',
            'account_status' => 'active',
        ]);

        foreach ($routes as $route) {
            $this->actingAs($user)
                ->getJson($route)
                ->assertForbidden();
        }

        $inactiveAdmin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'inactive',
        ]);

        foreach ($routes as $route) {
            $this->actingAs($inactiveAdmin)
                ->getJson($route)
                ->assertForbidden();
        }
    }

    public function test_axle_scan_resolves_canonical_values_and_saves_complete_snapshot(): void
    {
        $payload = array_merge($this->axlePayload(), [
            'shift' => '1',
            'production_line_id' => $this->productionLine->id,
            'scan_time' => '08:30',
            'axle_assy_type' => 'FORGED TYPE',
            'axle_side' => 'FORGED POSITION',
            'line' => 'FORGED LINE',
        ]);

        $this->actingAs($this->admin)
            ->post(route('documents.scan.store'), $payload)
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $document = Document::query()->sole();
        $metadata = DocumentScanMetadata::query()->sole();
        $snapshot = DocumentAxleProduction::query()->sole();

        $this->assertSame(
            'LAPORAN HASIL PRODUKSI - AXLE ASSY - Module Assy - LH - 2026-09-15',
            $document->title
        );
        $this->assertNull($document->part_number);
        $this->assertSame('Module Assy', $metadata->axle_assy_type);
        $this->assertSame('LH', $metadata->axle_side);
        $this->assertSame('LINE_AXLE_BRAKE', $metadata->line);
        $this->assertSame('1', (string) $metadata->shift);
        $this->assertSame('08:30', substr((string) $metadata->scan_time, 0, 5));

        $this->assertSame($document->id, $snapshot->document_id);
        $this->assertSame($this->moduleType->id, $snapshot->axle_type_id);
        $this->assertSame($this->leftPosition->id, $snapshot->axle_position_id);
        $this->assertSame($this->productionLine->id, $snapshot->production_line_id);
        $this->assertSame(1, $snapshot->shift);
        $this->assertSame('08:30', substr((string) $snapshot->production_time, 0, 5));
        $this->assertSame('MODULE_ASSY', $snapshot->snapshot_axle_type_code);
        $this->assertSame('Module Assy', $snapshot->snapshot_axle_type_name);
        $this->assertSame('LH', $snapshot->snapshot_position_code);
        $this->assertSame('LH', $snapshot->snapshot_position_name);
        $this->assertSame('LINE_AXLE_BRAKE', $snapshot->snapshot_line_code);
        $this->assertSame('MOP 3', $snapshot->snapshot_line_name);
        $this->assertTrue(Storage::disk('bmc_documents')->exists($document->file_path));

        $this->moduleType->update(['name' => 'Renamed Master Type']);
        $this->productionLine->update(['name' => 'Renamed Master Line']);
        $metadata->forceFill([
            'axle_assy_type' => 'FORGED LEGACY TYPE',
            'axle_side' => 'FORGED LEGACY POSITION',
            'line' => 'FORGED LEGACY LINE',
        ])->save();

        $snapshot->refresh();
        $this->assertSame('Module Assy', $snapshot->snapshot_axle_type_name);
        $this->assertSame('MOP 3', $snapshot->snapshot_line_name);

        $this->get(route('documents.show', $document))
            ->assertOk()
            ->assertSeeText('Data produksi saat dokumen ini dibuat.')
            ->assertSeeText('Kode: MODULE_ASSY')
            ->assertSeeText('MOP 3')
            ->assertSeeText('08:30')
            ->assertDontSeeText('FORGED LEGACY TYPE')
            ->assertDontSeeText('FORGED LEGACY LINE')
            ->assertDontSeeText('Renamed Master Line');
    }

    public function test_brake_scan_resolves_canonical_values_and_saves_complete_snapshot(): void
    {
        $payload = array_merge($this->brakePayload(), [
            'shift' => '3',
            'production_line_id' => $this->productionLine->id,
            'scan_time' => '23:45',
            'brake_part_prefix' => 'FORGED PREFIX',
            'brake_part_variant' => 'FORGED VARIANT',
            'brake_part_number' => 'FORGED PART NUMBER',
            'line' => 'FORGED LINE',
        ]);

        $this->actingAs($this->admin)
            ->post(route('documents.scan.store'), $payload)
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $document = Document::query()->sole();
        $metadata = DocumentScanMetadata::query()->sole();
        $snapshot = DocumentBrakeProduction::query()->sole();

        $this->assertSame(
            'LAPORAN HASIL PRODUKSI - BRAKE ASSY - A 4304200 402 / 502 - 2026-09-15',
            $document->title
        );
        $this->assertSame('A 4304200 402 / 502', $document->part_number);
        $this->assertSame('A 4304200', $metadata->brake_part_prefix);
        $this->assertSame('402 / 502', $metadata->brake_part_variant);
        $this->assertSame('A 4304200 402 / 502', $metadata->brake_part_number);
        $this->assertSame('LINE_AXLE_BRAKE', $metadata->line);

        $this->assertSame($document->id, $snapshot->document_id);
        $this->assertSame($this->brake402->id, $snapshot->brake_part_number_id);
        $this->assertSame($this->productionLine->id, $snapshot->production_line_id);
        $this->assertSame(3, $snapshot->shift);
        $this->assertSame('23:45', substr((string) $snapshot->production_time, 0, 5));
        $this->assertSame('A 4304200', $snapshot->snapshot_prefix);
        $this->assertSame('402 / 502', $snapshot->snapshot_variant);
        $this->assertSame('A 4304200 402 / 502', $snapshot->snapshot_display_name);
        $this->assertSame('LINE_AXLE_BRAKE', $snapshot->snapshot_line_code);
        $this->assertSame('MOP 3', $snapshot->snapshot_line_name);

        $this->brake402->update([
            'prefix' => 'CHANGED PREFIX',
            'variant' => 'CHANGED VARIANT',
            'display_name' => 'CHANGED MASTER PART',
        ]);
        $metadata->forceFill([
            'brake_part_prefix' => 'FORGED LEGACY PREFIX',
            'brake_part_variant' => 'FORGED LEGACY VARIANT',
            'brake_part_number' => 'FORGED LEGACY PART',
            'line' => 'FORGED LEGACY LINE',
        ])->save();

        $snapshot->refresh();
        $this->assertSame('A 4304200', $snapshot->snapshot_prefix);
        $this->assertSame('402 / 502', $snapshot->snapshot_variant);

        $this->get(route('documents.show', $document))
            ->assertOk()
            ->assertSeeText('Data produksi saat dokumen ini dibuat.')
            ->assertSeeText('A 4304200 402 / 502')
            ->assertSeeText('MOP 3')
            ->assertSeeText('23:45')
            ->assertDontSeeText('FORGED LEGACY PART')
            ->assertDontSeeText('CHANGED MASTER PART');
    }

    public function test_axle_and_brake_allow_shift_line_and_time_to_be_null(): void
    {
        $this->actingAs($this->admin)
            ->post(route('documents.scan.store'), $this->axlePayload())
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasNoErrors();

        $this->post(route('documents.scan.store'), $this->brakePayload())
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasNoErrors();

        $axleSnapshot = DocumentAxleProduction::query()->sole();
        $brakeSnapshot = DocumentBrakeProduction::query()->sole();

        foreach ($axleSnapshot as $unused) {
            // Keep Eloquent from treating the assertions below as an array cast.
            break;
        }

        $this->assertNull($axleSnapshot->shift);
        $this->assertNull($axleSnapshot->production_line_id);
        $this->assertNull($axleSnapshot->production_time);
        $this->assertNull($axleSnapshot->snapshot_line_code);
        $this->assertNull($axleSnapshot->snapshot_line_name);

        $this->assertNull($brakeSnapshot->shift);
        $this->assertNull($brakeSnapshot->production_line_id);
        $this->assertNull($brakeSnapshot->production_time);
        $this->assertNull($brakeSnapshot->snapshot_line_code);
        $this->assertNull($brakeSnapshot->snapshot_line_name);

        $metadataRows = DocumentScanMetadata::query()
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $metadataRows);

        foreach ($metadataRows as $metadata) {
            $this->assertNull($metadata->shift);
            $this->assertNull($metadata->line);
            $this->assertNull($metadata->scan_time);
        }
    }

    public function test_axle_and_brake_allow_shift_without_time(): void
    {
        $axlePayload = $this->axlePayload();
        $axlePayload['shift'] = '1';

        $brakePayload = $this->brakePayload();
        $brakePayload['shift'] = '3';

        $this->actingAs($this->admin)
            ->post(route('documents.scan.store'), $axlePayload)
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasNoErrors();

        $this->post(route('documents.scan.store'), $brakePayload)
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasNoErrors();

        $axleSnapshot = DocumentAxleProduction::query()->sole();
        $brakeSnapshot = DocumentBrakeProduction::query()->sole();

        $this->assertSame(1, $axleSnapshot->shift);
        $this->assertNull($axleSnapshot->production_time);
        $this->assertSame(3, $brakeSnapshot->shift);
        $this->assertNull($brakeSnapshot->production_time);

        $metadataRows = DocumentScanMetadata::query()
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $metadataRows);
        $this->assertSame('1', (string) $metadataRows[0]->shift);
        $this->assertNull($metadataRows[0]->scan_time);
        $this->assertSame('3', (string) $metadataRows[1]->shift);
        $this->assertNull($metadataRows[1]->scan_time);
    }

    public function test_axle_and_brake_reject_time_without_shift_before_artifacts(): void
    {
        $this->actingAs($this->admin);

        foreach ([$this->axlePayload(), $this->brakePayload()] as $payload) {
            $payload['scan_time'] = '08:30';

            $this->post(route('documents.scan.store'), $payload)
                ->assertRedirect(route('documents.scan.create'))
                ->assertSessionHasErrors([
                    'shift' => 'Pilih Shift ketika Jam diisi.',
                ])
                ->assertSessionHasInput('scan_time', '08:30');
        }

        $this->assertNoScanArtifacts();
    }

    public function test_axle_and_brake_reject_time_outside_selected_shift_before_artifacts(): void
    {
        $this->actingAs($this->admin);

        $cases = [
            [$this->axlePayload(), '1', '20:00', '07:00–15:59'],
            [$this->brakePayload(), '3', '17:20', '23:00–06:59'],
        ];

        foreach ($cases as [$payload, $shift, $time, $rangeLabel]) {
            $payload['shift'] = $shift;
            $payload['scan_time'] = $time;

            $this->post(route('documents.scan.store'), $payload)
                ->assertRedirect(route('documents.scan.create'))
                ->assertSessionHasErrors([
                    'scan_time' => "Jam {$time} tidak sesuai dengan Shift {$shift} ({$rangeLabel}).",
                ]);
        }

        $this->assertNoScanArtifacts();
    }

    public function test_other_scan_rejects_mismatched_shift_time_before_artifacts(): void
    {
        $payload = array_merge($this->basePayload(), [
            'scan_type' => 'Lainnya',
            'scan_type_other' => 'Audit Internal QC',
            'title' => 'Dokumen Audit Internal',
            'shift' => '2',
            'scan_time' => '11:05',
        ]);

        $this->actingAs($this->admin)
            ->post(route('documents.scan.store'), $payload)
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasErrors([
                'scan_time' => 'Jam 11:05 tidak sesuai dengan Shift 2 (16:00–22:59).',
            ]);

        $this->assertNoScanArtifacts();
    }

    public function test_inactive_and_unknown_axle_master_ids_are_rejected_without_artifacts(): void
    {
        $this->actingAs($this->admin);

        $this->moduleType->update(['is_active' => false]);
        $this->post(route('documents.scan.store'), $this->axlePayload())
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasErrors('axle_type_id');

        $this->moduleType->update(['is_active' => true]);
        $this->leftPosition->update(['is_active' => false]);
        $this->post(route('documents.scan.store'), $this->axlePayload())
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasErrors('axle_position_id');

        $this->leftPosition->update(['is_active' => true]);
        $payload = $this->axlePayload();
        $payload['axle_type_id'] = 999999;
        $this->post(route('documents.scan.store'), $payload)
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasErrors('axle_type_id');

        $payload = $this->axlePayload();
        $payload['axle_position_id'] = 999999;
        $this->post(route('documents.scan.store'), $payload)
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasErrors('axle_position_id');

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_axle_productions', 0);
        $this->assertSame([], Storage::disk('bmc_documents')->allFiles());
    }

    public function test_inactive_and_unknown_brake_master_ids_are_rejected_without_artifacts(): void
    {
        $this->actingAs($this->admin);

        $this->brake402->update(['is_active' => false]);
        $this->post(route('documents.scan.store'), $this->brakePayload())
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasErrors('brake_part_number_id');

        $this->brake402->update(['is_active' => true]);
        $payload = $this->brakePayload();
        $payload['brake_part_number_id'] = 999999;
        $this->post(route('documents.scan.store'), $payload)
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasErrors('brake_part_number_id');

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_brake_productions', 0);
        $this->assertSame([], Storage::disk('bmc_documents')->allFiles());
    }

    public function test_axle_and_brake_reject_inactive_optional_line(): void
    {
        $this->productionLine->update(['is_active' => false]);
        $this->actingAs($this->admin);

        foreach ([$this->axlePayload(), $this->brakePayload()] as $payload) {
            $payload['production_line_id'] = $this->productionLine->id;

            $this->post(route('documents.scan.store'), $payload)
                ->assertRedirect(route('documents.scan.create'))
                ->assertSessionHasErrors('production_line_id');
        }

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_axle_productions', 0);
        $this->assertDatabaseCount('document_brake_productions', 0);
        $this->assertSame([], Storage::disk('bmc_documents')->allFiles());
    }

    public function test_optional_shift_and_time_are_validated_when_present(): void
    {
        $this->actingAs($this->admin);

        foreach ([$this->axlePayload(), $this->brakePayload()] as $payload) {
            $payload['shift'] = '4';
            $payload['scan_time'] = '25:00';

            $this->post(route('documents.scan.store'), $payload)
                ->assertRedirect(route('documents.scan.create'))
                ->assertSessionHasErrors([
                    'shift',
                    'scan_time',
                ]);
        }

        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], Storage::disk('bmc_documents')->allFiles());
    }

    public function test_storage_failure_leaves_no_axle_or_brake_document_snapshot_or_file(): void
    {
        $blockedRoot = $this->storageRoot . '-blocked';
        File::put($blockedRoot, 'This file cannot be used as a storage directory.');

        config(['filesystems.disks.bmc_documents.root' => $blockedRoot]);
        Storage::purge('bmc_documents');

        $this->actingAs($this->admin);

        foreach ([$this->axlePayload(), $this->brakePayload()] as $payload) {
            $this->post(route('documents.scan.store'), $payload)
                ->assertRedirect(route('documents.scan.create'))
                ->assertSessionHasErrors('files');
        }

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_axle_productions', 0);
        $this->assertDatabaseCount('document_brake_productions', 0);
        $this->assertDatabaseCount('document_scan_metadata', 0);
        $this->assertSame([], File::allFiles($this->storageRoot));
    }

    public function test_detail_uses_legacy_metadata_only_when_relational_snapshot_is_absent(): void
    {
        $this->actingAs($this->admin)
            ->post(route('documents.scan.store'), $this->axlePayload())
            ->assertSessionHasNoErrors();

        $axleDocument = Document::query()->latest('id')->firstOrFail();
        $axleDocument->axleProduction()->delete();
        $axleDocument->scanMetadata()->update([
            'axle_assy_type' => 'Legacy Module',
            'axle_side' => 'Legacy LH',
            'shift' => '2',
            'line' => 'LEGACY AXLE LINE',
            'scan_time' => '06:15',
        ]);

        $this->get(route('documents.show', $axleDocument))
            ->assertOk()
            ->assertSeeText('Metadata produksi dari dokumen lama.')
            ->assertSeeText('Legacy Module')
            ->assertSeeText('Legacy LH')
            ->assertSeeText('Shift 2')
            ->assertSeeText('LEGACY AXLE LINE')
            ->assertSeeText('06:15');

        $this->post(route('documents.scan.store'), $this->brakePayload())
            ->assertSessionHasNoErrors();

        $brakeDocument = Document::query()->latest('id')->firstOrFail();
        $brakeDocument->brakeProduction()->delete();
        $brakeDocument->scanMetadata()->update([
            'brake_part_prefix' => 'LEGACY PREFIX',
            'brake_part_variant' => 'LEGACY VARIANT',
            'brake_part_number' => 'LEGACY FULL PART',
            'shift' => '3',
            'line' => 'LEGACY BRAKE LINE',
            'scan_time' => '17:20',
        ]);

        $this->get(route('documents.show', $brakeDocument))
            ->assertOk()
            ->assertSeeText('Metadata produksi dari dokumen lama.')
            ->assertSeeText('LEGACY PREFIX')
            ->assertSeeText('LEGACY VARIANT')
            ->assertSeeText('LEGACY FULL PART')
            ->assertSeeText('Shift 3')
            ->assertSeeText('LEGACY BRAKE LINE')
            ->assertSeeText('17:20');
    }

    public function test_other_scan_workflow_does_not_create_production_snapshot(): void
    {
        $payload = array_merge($this->basePayload(), [
            'scan_type' => 'Lainnya',
            'scan_type_other' => 'Audit Internal QC',
            'title' => 'Dokumen Audit Internal',
            'line' => 'LEGACY FREE TEXT LINE',
            'shift' => '2',
            'scan_time' => '16:05',
        ]);

        $this->actingAs($this->admin)
            ->post(route('documents.scan.store'), $payload)
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasNoErrors();

        $document = Document::query()->sole();
        $metadata = DocumentScanMetadata::query()->sole();

        $this->assertSame('Dokumen Audit Internal', $document->title);
        $this->assertSame('Audit Internal QC', $metadata->scan_type);
        $this->assertSame('LEGACY FREE TEXT LINE', $metadata->line);
        $this->assertSame('2', (string) $metadata->shift);
        $this->assertSame('16:05', substr((string) $metadata->scan_time, 0, 5));
        $this->assertDatabaseCount('document_axle_productions', 0);
        $this->assertDatabaseCount('document_brake_productions', 0);
        $this->assertDatabaseCount('document_retoolings', 0);
        $this->assertTrue(Storage::disk('bmc_documents')->exists($document->file_path));
    }

    private function axlePayload(): array
    {
        return array_merge($this->basePayload(), [
            'scan_type' => 'Laporan Hasil Produksi - Axle Assy',
            'axle_type_id' => $this->moduleType->id,
            'axle_position_id' => $this->leftPosition->id,
        ]);
    }

    private function brakePayload(): array
    {
        return array_merge($this->basePayload(), [
            'scan_type' => 'Laporan Hasil Produksi - Brake Assy',
            'brake_part_number_id' => $this->brake402->id,
        ]);
    }

    private function basePayload(): array
    {
        return [
            'category_id' => $this->category->id,
            'title' => 'Scanned QC Document',
            'file_name' => 'qc-production-photo',
            'scan_date' => '2026-09-15',
            'capture_source' => 'upload',
            'files' => [UploadedFile::fake()->image('scan.png', 2, 2)],
        ];
    }

    private function assertNoScanArtifacts(): void
    {
        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_scan_metadata', 0);
        $this->assertDatabaseCount('document_axle_productions', 0);
        $this->assertDatabaseCount('document_brake_productions', 0);
        $this->assertDatabaseCount('document_retoolings', 0);
        $this->assertDatabaseCount('document_versions', 0);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertSame([], Storage::disk('bmc_documents')->allFiles());
    }
}
