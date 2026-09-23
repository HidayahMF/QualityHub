<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentAnalytics;
use App\Models\DocumentAnalyticSheet;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class DocumentPermanentDeleteTest extends TestCase
{
    use RefreshDatabase;

    private string $storageRoot;

    private Category $category;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageRoot = storage_path(
            'framework/testing/document-permanent-delete-'.bin2hex(random_bytes(8))
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
            'name' => 'Inspection',
            'slug' => 'inspection',
            'is_active' => true,
        ]);
        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Storage::purge('bmc_documents');
        File::deleteDirectory($this->storageRoot);

        parent::tearDown();
    }

    public function test_guest_cannot_permanently_delete_archived_document(): void
    {
        $document = $this->createDocument();
        $path = $document->file_path;

        $this->delete(route('documents.force-destroy', $document), [
            'confirmation' => 'HAPUS',
        ])->assertRedirect(route('login'));

        $this->assertNotNull($document->fresh());
        $this->assertTrue(Storage::disk('bmc_documents')->exists($path));
        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'permanent_delete_document',
        ]);
    }

    #[DataProvider('nonSuperAdminProvider')]
    public function test_non_super_admin_cannot_permanently_delete_archived_document(string $role): void
    {
        $document = $this->createDocument();
        $actor = User::factory()->create([
            'role' => $role,
            'account_status' => 'active',
        ]);

        $this->actingAs($actor)
            ->delete(route('documents.force-destroy', $document), [
                'confirmation' => 'HAPUS',
            ])
            ->assertForbidden();

        $this->assertNotNull($document->fresh());
        $this->assertTrue(Storage::disk('bmc_documents')->exists($document->file_path));
        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'permanent_delete_document',
        ]);
    }

    public static function nonSuperAdminProvider(): array
    {
        return [
            'admin' => ['admin'],
            'user' => ['user'],
            'viewer' => ['viewer'],
        ];
    }

    #[DataProvider('invalidDocumentIdProvider')]
    public function test_invalid_document_id_returns_not_found_without_deleting_anything(string $id): void
    {
        $existing = $this->createDocument();

        $this->actingAs($this->superAdmin)
            ->delete(route('documents.force-destroy', $id), [
                'confirmation' => 'HAPUS',
            ])
            ->assertNotFound();

        $this->assertNotNull($existing->fresh());
        $this->assertTrue(Storage::disk('bmc_documents')->exists($existing->file_path));
        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'permanent_delete_document',
        ]);
    }

    public static function invalidDocumentIdProvider(): array
    {
        return [
            'malformed' => ['not-a-number'],
            'nonexistent' => ['999999'],
        ];
    }

    public function test_active_document_cannot_be_permanently_deleted(): void
    {
        $document = $this->createDocument(archived: false);

        $this->actingAs($this->superAdmin)
            ->delete(route('documents.force-destroy', $document), [
                'confirmation' => 'HAPUS',
            ])
            ->assertSessionHasErrors('document');

        $this->assertNotNull($document->fresh());
        $this->assertTrue(Storage::disk('bmc_documents')->exists($document->file_path));
        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'permanent_delete_document',
        ]);
    }

    #[DataProvider('invalidConfirmationProvider')]
    public function test_confirmation_must_be_exact_uppercase_hapus(string $confirmation): void
    {
        $document = $this->createDocument();

        $this->actingAs($this->superAdmin)
            ->delete(route('documents.force-destroy', $document), [
                'confirmation' => $confirmation,
            ])
            ->assertSessionHasErrors('confirmation');

        $this->assertNotNull($document->fresh());
        $this->assertTrue(Storage::disk('bmc_documents')->exists($document->file_path));
    }

    public static function invalidConfirmationProvider(): array
    {
        return [
            'empty' => [''],
            'lowercase' => ['hapus'],
            'wrong word' => ['HAPUS SEMUA'],
        ];
    }

    public function test_super_admin_can_purge_full_document_graph_and_files_while_preserving_audit(): void
    {
        $document = $this->createDocument();
        $revisionPath = "documents/{$document->id}/rev-0.xlsx";
        $currentPath = $document->file_path;
        Storage::disk('bmc_documents')->put($revisionPath, 'older version');
        Storage::disk('bmc_documents')->put('unrelated/keep.pdf', 'keep');

        $firstVersion = $this->createVersion($document, 0, $revisionPath);
        $currentVersion = $this->createVersion($document, 1, $currentPath);

        foreach ([$firstVersion, $currentVersion] as $version) {
            $analytics = DocumentAnalytics::create([
                'document_id' => $document->id,
                'document_version_id' => $version->id,
                'revision_number' => $version->revision_number,
                'status' => 'ready',
            ]);
            DocumentAnalyticSheet::create([
                'document_analytics_id' => $analytics->id,
                'sheet_name' => 'Data',
                'sheet_index' => 0,
            ]);
        }

        $this->seedOtherDocumentChildren($document);

        $this->assertSame(1, Category::query()->withCount('documents')->findOrFail($this->category->id)->documents_count);

        $priorLog = ActivityLog::create([
            'user_id' => $this->superAdmin->id,
            'document_id' => $document->id,
            'action' => 'archive_document',
            'description' => 'Archived before purge.',
            'metadata' => ['document_id' => $document->id],
        ]);

        $this->actingAs($this->superAdmin)
            ->delete(route('documents.force-destroy', $document), [
                'confirmation' => ' HAPUS ',
            ])
            ->assertRedirect(route('documents.archived'))
            ->assertSessionHasNoErrors();

        foreach ([
            'documents',
            'document_versions',
            'document_analytics',
            'document_analytic_sheets',
            'favorites',
            'document_scan_metadata',
            'document_retoolings',
            'document_axle_productions',
            'document_brake_productions',
        ] as $table) {
            $this->assertDatabaseCount($table, 0);
        }

        $this->assertDatabaseHas('activity_logs', [
            'id' => $priorLog->id,
            'document_id' => null,
            'action' => 'archive_document',
        ]);
        $purgeLog = ActivityLog::query()
            ->where('action', 'permanent_delete_document')
            ->sole();
        $this->assertNull($purgeLog->document_id);
        $this->assertSame($this->superAdmin->id, $purgeLog->user_id);
        $this->assertSame($document->id, $purgeLog->metadata['document_id']);

        $this->assertFalse(Storage::disk('bmc_documents')->exists($revisionPath));
        $this->assertFalse(Storage::disk('bmc_documents')->exists($currentPath));
        $this->assertTrue(Storage::disk('bmc_documents')->exists('unrelated/keep.pdf'));
        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseCount('departments', 1);
        $this->assertDatabaseCount('production_lines', 1);
        $this->assertDatabaseCount('castings', 2);
        $this->assertDatabaseCount('axle_types', 1);
        $this->assertDatabaseCount('axle_positions', 1);
        $this->assertDatabaseCount('brake_part_numbers', 1);
        $this->assertSame(0, Category::query()->withCount('documents')->findOrFail($this->category->id)->documents_count);
    }

    public function test_database_failure_rolls_back_every_child_and_keeps_files(): void
    {
        $document = $this->createDocument();
        $version = $this->createVersion($document, 0, $document->file_path);
        $analytics = DocumentAnalytics::create([
            'document_id' => $document->id,
            'document_version_id' => $version->id,
            'revision_number' => 0,
            'status' => 'ready',
        ]);
        DocumentAnalyticSheet::create([
            'document_analytics_id' => $analytics->id,
            'sheet_name' => 'Data',
            'sheet_index' => 0,
        ]);

        DB::listen(function (QueryExecuted $query): void {
            if (preg_match('/delete from [`"]?document_versions[`"]?/i', $query->sql)) {
                throw new RuntimeException('Simulated version deletion failure.');
            }
        });

        $this->actingAs($this->superAdmin)
            ->delete(route('documents.force-destroy', $document), [
                'confirmation' => 'HAPUS',
            ])
            ->assertSessionHasErrors('document');

        $this->assertDatabaseHas('documents', ['id' => $document->id]);
        $this->assertDatabaseHas('document_versions', ['id' => $version->id]);
        $this->assertDatabaseHas('document_analytics', ['id' => $analytics->id]);
        $this->assertDatabaseCount('document_analytic_sheets', 1);
        $this->assertTrue(Storage::disk('bmc_documents')->exists($document->file_path));
        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'permanent_delete_document',
        ]);
    }

    public function test_file_still_referenced_by_another_document_is_preserved(): void
    {
        $archived = $this->createDocument();
        $other = $this->createDocument(archived: false);
        $this->createVersion($archived, 0, $other->file_path);

        $this->actingAs($this->superAdmin)
            ->delete(route('documents.force-destroy', $archived), [
                'confirmation' => 'HAPUS',
            ])
            ->assertRedirect(route('documents.archived'))
            ->assertSessionHasNoErrors();

        $this->assertNull($archived->fresh());
        $this->assertNotNull($other->fresh());
        $this->assertFalse(Storage::disk('bmc_documents')->exists($archived->file_path));
        $this->assertTrue(Storage::disk('bmc_documents')->exists($other->file_path));

        $purgeLog = ActivityLog::query()
            ->where('action', 'permanent_delete_document')
            ->sole();
        $this->assertSame(1, $purgeLog->metadata['preserved_shared_file_count']);
    }

    public function test_failed_physical_cleanup_keeps_committed_purge_and_warns_user(): void
    {
        $document = $this->createDocument();
        $path = $document->file_path;
        $version = $this->createVersion($document, 0, $path);

        $realDisk = Storage::disk('bmc_documents');
        $failingDisk = Mockery::mock($realDisk)->makePartial();
        $failingDisk->shouldReceive('delete')
            ->once()
            ->with($path)
            ->andReturn(false);
        Storage::set('bmc_documents', $failingDisk);

        try {
            $response = $this->actingAs($this->superAdmin)
                ->delete(route('documents.force-destroy', $document), [
                    'confirmation' => 'HAPUS',
                ]);
        } finally {
            Storage::set('bmc_documents', $realDisk);
        }

        $response
            ->assertRedirect(route('documents.archived'))
            ->assertSessionHas('warning');

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        $this->assertDatabaseMissing('document_versions', ['id' => $version->id]);
        $this->assertTrue(Storage::disk('bmc_documents')->exists($path));

        $purgeLog = ActivityLog::query()
            ->where('action', 'permanent_delete_document')
            ->sole();
        $this->assertNull($purgeLog->document_id);
        $this->assertSame($document->id, $purgeLog->metadata['document_id']);
        $this->assertSame('failed', $purgeLog->metadata['file_cleanup_status']);
        $this->assertSame(1, $purgeLog->metadata['failed_file_count']);
    }

    public function test_distinct_relative_paths_to_same_physical_file_are_preserved(): void
    {
        $archived = $this->createDocument();
        $other = $this->createDocument(archived: false);
        $disk = Storage::disk('bmc_documents');
        $disk->delete($other->file_path);

        if (DIRECTORY_SEPARATOR === '\\') {
            $aliasPath = str_replace('/', '\\', $archived->file_path);
        } else {
            $aliasPath = 'aliases/'.$archived->id.'/current.xlsx';
            File::ensureDirectoryExists(dirname($disk->path($aliasPath)));

            if (! @symlink($disk->path($archived->file_path), $disk->path($aliasPath))) {
                $this->markTestSkipped('The local filesystem does not permit an in-root file symlink.');
            }
        }

        $this->assertNotSame($archived->file_path, $aliasPath);
        $this->assertSame(
            realpath($disk->path($archived->file_path)),
            realpath($disk->path($aliasPath))
        );
        $other->update(['file_path' => $aliasPath]);

        $this->actingAs($this->superAdmin)
            ->delete(route('documents.force-destroy', $archived), [
                'confirmation' => 'HAPUS',
            ])
            ->assertRedirect(route('documents.archived'))
            ->assertSessionHasNoErrors();

        $this->assertNull($archived->fresh());
        $this->assertNotNull($other->fresh());
        $this->assertTrue($disk->exists($archived->file_path));
        $this->assertTrue($disk->exists($aliasPath));

        $purgeLog = ActivityLog::query()
            ->where('action', 'permanent_delete_document')
            ->sole();
        $this->assertSame(1, $purgeLog->metadata['preserved_shared_file_count']);
    }

    public function test_archived_page_exposes_closed_purge_modal_only_to_super_admin(): void
    {
        $archived = $this->createDocument();
        $active = $this->createDocument(archived: false);
        $purgeUrl = route('documents.force-destroy', $archived);

        $superAdminPage = $this->actingAs($this->superAdmin)
            ->get(route('documents.archived'))
            ->assertOk();
        $superAdminPage
            ->assertSee('action="'.$purgeUrl.'"', false)
            ->assertSee('name="confirmation"', false)
            ->assertSee('name="_method" value="DELETE"', false)
            ->assertSee('data-qh-modal-show="false"', false)
            ->assertDontSee('data-qh-modal-show="true"', false)
            ->assertDontSee(route('documents.force-destroy', $active), false);
    }

    public function test_admin_archived_page_does_not_expose_purge_action(): void
    {
        $archived = $this->createDocument();
        $purgeUrl = route('documents.force-destroy', $archived);
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);
        $this->actingAs($admin)
            ->get(route('documents.archived'))
            ->assertOk()
            ->assertDontSee($purgeUrl, false);
    }

    private function createDocument(bool $archived = true): Document
    {
        $document = Document::create([
            'category_id' => $this->category->id,
            'uploaded_by' => $this->superAdmin->id,
            'title' => $archived ? 'Archived Quality Record' : 'Active Quality Record',
            'file_name' => 'quality-record.xlsx',
            'file_path' => 'documents/'.bin2hex(random_bytes(6)).'/current.xlsx',
            'file_extension' => 'xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'file_size' => 16,
            'is_active' => ! $archived,
            'archived_at' => $archived ? now() : null,
            'archived_by' => $archived ? $this->superAdmin->id : null,
            'archive_reason' => $archived ? 'No longer needed' : null,
            'current_revision' => 1,
        ]);

        Storage::disk('bmc_documents')->put($document->file_path, 'current version');

        return $document;
    }

    private function createVersion(Document $document, int $revision, string $path): DocumentVersion
    {
        return DocumentVersion::create([
            'document_id' => $document->id,
            'revision_number' => $revision,
            'uploaded_by' => $this->superAdmin->id,
            'file_name' => basename($path),
            'file_path' => $path,
            'file_extension' => 'xlsx',
            'file_size' => 16,
        ]);
    }

    private function seedOtherDocumentChildren(Document $document): void
    {
        $lineId = DB::table('production_lines')->insertGetId([
            'code' => 'LINE-1',
            'name' => 'Line 1',
        ]);
        $firstCastingId = DB::table('castings')->insertGetId([
            'code' => 'CAST-A',
            'name' => 'Casting A',
        ]);
        $secondCastingId = DB::table('castings')->insertGetId([
            'code' => 'CAST-B',
            'name' => 'Casting B',
        ]);
        $axleTypeId = DB::table('axle_types')->insertGetId([
            'code' => 'AXLE-A',
            'name' => 'Axle A',
        ]);
        $axlePositionId = DB::table('axle_positions')->insertGetId([
            'code' => 'LEFT',
            'name' => 'Left',
        ]);
        $brakePartId = DB::table('brake_part_numbers')->insertGetId([
            'prefix' => 'BRK',
            'variant' => 'A',
            'display_name' => 'Brake A',
        ]);

        DB::table('favorites')->insert([
            'user_id' => $this->superAdmin->id,
            'document_id' => $document->id,
        ]);
        DB::table('document_scan_metadata')->insert([
            'document_id' => $document->id,
        ]);
        DB::table('document_retoolings')->insert([
            'document_id' => $document->id,
            'shift' => 1,
            'production_line_id' => $lineId,
            'from_casting_id' => $firstCastingId,
            'to_casting_id' => $secondCastingId,
            'from_cycle_time' => 1.25,
            'to_cycle_time' => 1.50,
        ]);
        DB::table('document_axle_productions')->insert([
            'document_id' => $document->id,
            'axle_type_id' => $axleTypeId,
            'axle_position_id' => $axlePositionId,
            'production_line_id' => $lineId,
            'snapshot_axle_type_code' => 'AXLE-A',
            'snapshot_axle_type_name' => 'Axle A',
            'snapshot_position_code' => 'LEFT',
            'snapshot_position_name' => 'Left',
        ]);
        DB::table('document_brake_productions')->insert([
            'document_id' => $document->id,
            'brake_part_number_id' => $brakePartId,
            'production_line_id' => $lineId,
            'snapshot_prefix' => 'BRK',
            'snapshot_variant' => 'A',
            'snapshot_display_name' => 'Brake A',
        ]);
    }
}
