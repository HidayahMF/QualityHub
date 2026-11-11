<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentRetooling;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RetoolingExternalSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private string $storageRoot;

    // SQLite ATTACH must happen BEFORE RefreshDatabase starts its transaction.
    // This models the production database schemas mtc.LINE and ppic.LINEprd.
    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        $pdo = DB::connection()->getPdo();
        $pdo->exec("ATTACH DATABASE ':memory:' AS mtc");
        $pdo->exec("ATTACH DATABASE ':memory:' AS ppic");

        DB::statement('CREATE TABLE "mtc"."LINE" (
            "KODE_LN" INTEGER PRIMARY KEY, "kd_ln" TEXT, "NAMA_LN" TEXT
        )');
        DB::statement('CREATE TABLE "ppic"."LINEprd" (
            "id_eo" INTEGER PRIMARY KEY, "KODE_LN" INTEGER,
            "KODE_CST" TEXT, "NAMA_CST" TEXT, "CT" REAL, "AKTIF" INTEGER
        )');
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('Fixture MTC/PPIC ini hanya untuk SQLite testing.');
        }

        $this->storageRoot = storage_path('framework/testing/retooling-external-' . bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($this->storageRoot);
        config(['filesystems.disks.bmc_documents' => [
            'driver' => 'local',
            'root' => $this->storageRoot,
            'throw' => false,
        ]]);
        Storage::purge('bmc_documents');
    }

    protected function tearDown(): void
    {
        Storage::purge('bmc_documents');

        if (isset($this->storageRoot)) {
            File::deleteDirectory($this->storageRoot);
        }

        parent::tearDown();
    }

    public function test_mtc_ppic_scan_persists_snapshot_without_guessing_local_foreign_keys(): void
    {
        $department = Department::create([
            'name' => 'Quality Control',
            'code' => 'QC',
            'is_active' => true,
        ]);
        $category = Category::create([
            'department_id' => $department->id,
            'name' => 'Retooling QC',
            'slug' => 'retooling-qc',
            'is_active' => true,
        ]);
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);

        // MTC/PPIC identifiers intentionally have no corresponding local FK IDs.
        DB::table('mtc.LINE')->insert([
            'KODE_LN' => 902,
            'kd_ln' => 'MTC-902',
            'NAMA_LN' => 'Assembly Line 902',
        ]);
        DB::table('ppic.LINEprd')->insert([
            [
                'id_eo' => 1, 'KODE_LN' => 902, 'KODE_CST' => 'CAST_A',
                'NAMA_CST' => 'Casting Alpha', 'CT' => 25, 'AKTIF' => 1,
            ],
            [
                'id_eo' => 2, 'KODE_LN' => 902, 'KODE_CST' => 'CAST_B',
                'NAMA_CST' => 'Casting Beta', 'CT' => 22, 'AKTIF' => 1,
            ],
        ]);

        $this->actingAs($admin)
            ->post(route('documents.scan.store'), [
                'title' => 'Retooling QC',
                'file_name' => 'retooling-external-test',
                'category_id' => $category->id,
                'scan_type' => 'Retooling',
                'scan_date' => '2026-11-11',
                'shift' => '1',
                'scan_time' => '08:30',
                'production_line_id' => 902,
                'from_casting_id' => 'CAST_A',
                'to_casting_id' => 'CAST_B',
                'line' => 'FORGED LINE',
                'from_type' => 'FORGED FROM',
                'to_type' => 'FORGED TO',
                'from_cycle_time' => '999',
                'to_cycle_time' => '999',
                'files' => [UploadedFile::fake()->image('scan.png', 1200, 900)],
            ])
            ->assertRedirect(route('documents.scan.create'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $document = Document::query()->sole();
        $snapshot = DocumentRetooling::query()->sole();
        $revisionZero = DocumentVersion::query()->sole();

        $this->assertSame($document->id, $snapshot->document_id);
        $this->assertNull($snapshot->production_line_id);
        $this->assertNull($snapshot->from_casting_id);
        $this->assertNull($snapshot->to_casting_id);
        $this->assertSame('902', $snapshot->external_line_id);
        $this->assertSame('MTC-902', $snapshot->external_line_code);
        $this->assertSame('Assembly Line 902', $snapshot->external_line_name);
        $this->assertSame('CAST_A', $snapshot->external_from_casting_code);
        $this->assertSame('Casting Alpha', $snapshot->external_from_casting_name);
        $this->assertSame('CAST_B', $snapshot->external_to_casting_code);
        $this->assertSame('Casting Beta', $snapshot->external_to_casting_name);
        $this->assertSame('25.00', $snapshot->from_cycle_time);
        $this->assertSame('22.00', $snapshot->to_cycle_time);
        $this->assertSame('2026-11-11', $snapshot->retooling_date?->toDateString());
        $this->assertSame('CAST_A', $revisionZero->metadata_snapshot['scan_metadata']['from_type']);
        $this->assertSame('25.0', (string) $revisionZero->metadata_snapshot['scan_metadata']['from_cycle_time']);

        // Historical view should not depend on later edits to master PPIC/MTC.
        DB::table('mtc.LINE')->where('KODE_LN', 902)
            ->update(['NAMA_LN' => 'Renamed Line']);
        DB::table('ppic.LINEprd')->where('KODE_CST', 'CAST_A')
            ->update(['NAMA_CST' => 'Renamed Casting', 'CT' => 88]);

        $this->actingAs($admin)
            ->get(route('documents.show', $document))
            ->assertOk()
            ->assertSee('Assembly Line 902')
            ->assertSee('Casting Alpha')
            ->assertSee('25.00')
            ->assertDontSee('Renamed Casting');

        $this->assertSame('25.00', $snapshot->fresh()->from_cycle_time);
        $this->assertSame('Casting Alpha', $snapshot->fresh()->external_from_casting_name);
    }
}
