<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BulkDummyDocumentDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private Category $category;

    private string $storageRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageRoot = storage_path(
            'framework/testing/bulk-dummy-purge-'.bin2hex(random_bytes(8))
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

    private function createDocument(string $title, bool $active = true, ?string $sharedPath = null): Document
    {
        $path = $sharedPath ?? 'documents/'.bin2hex(random_bytes(6)).'/original.pdf';
        $document = Document::create([
            'category_id' => $this->category->id,
            'uploaded_by' => $this->superAdmin->id,
            'title' => $title,
            'file_name' => 'original.pdf',
            'file_path' => $path,
            'file_extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 12,
            'is_active' => $active,
            'archived_at' => $active ? null : now(),
            'archived_by' => $active ? null : $this->superAdmin->id,
            'archive_reason' => $active ? null : 'Previously archived',
            'current_revision' => 1,
        ]);

        Storage::disk('bmc_documents')->put($path, 'valid demo file');
        return $document;
    }

    private function payload(array $ids, array $extra = []): array
    {
        return array_merge([
            'document_ids' => $ids,
            'selected_count' => count($ids),
            'acknowledge_dummy' => '1',
            'confirmation' => 'HAPUS',
        ], $extra);
    }

    public function test_guest_cannot_bulk_delete_active_documents(): void
    {
        $document = $this->createDocument('Demo record');

        $this->delete(route('documents.bulk-force-destroy'), $this->payload([$document->id]))
            ->assertRedirect(route('login'));

        $this->assertNotNull($document->fresh());
        $this->assertTrue(Storage::disk('bmc_documents')->exists($document->file_path));
        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'permanent_delete_document',
        ]);
    }

    #[DataProvider('nonSuperAdminProvider')]
    public function test_non_super_admin_cannot_bulk_delete_active_documents(string $role): void
    {
        $document = $this->createDocument('Demo record');
        $actor = User::factory()->create([
            'role' => $role,
            'account_status' => 'active',
        ]);

        $this->actingAs($actor)
            ->delete(route('documents.bulk-force-destroy'), $this->payload([$document->id]))
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

    public function test_super_admin_bulk_deletes_selected_active_records_and_versions_with_audit(): void
    {
        $one = $this->createDocument('DUMMY Scan A');
        $two = $this->createDocument('DUMMY Scan B');
        $real = $this->createDocument('Actual Production QC');
        $revisionPath = 'documents/'.bin2hex(random_bytes(6)).'/revision.pdf';

        Storage::disk('bmc_documents')->put($revisionPath, 'demo revision');
        DocumentVersion::create([
            'document_id' => $one->id,
            'revision_number' => 0,
            'uploaded_by' => $this->superAdmin->id,
            'file_name' => 'revision.pdf',
            'file_path' => $revisionPath,
            'file_extension' => 'pdf',
            'file_size' => 13,
        ]);
        DB::table('document_scan_metadata')->insert([
            'document_id' => $two->id,
        ]);

        $this->actingAs($this->superAdmin)
            ->delete(route('documents.bulk-force-destroy'), $this->payload([$one->id, $two->id]))
            ->assertRedirect(route('documents.index'))
            ->assertSessionHas('success');

        $this->assertNull($one->fresh());
        $this->assertNull($two->fresh());
        $this->assertNotNull($real->fresh());
        $this->assertFalse(Storage::disk('bmc_documents')->exists($one->file_path));
        $this->assertFalse(Storage::disk('bmc_documents')->exists($two->file_path));
        $this->assertFalse(Storage::disk('bmc_documents')->exists($revisionPath));
        $this->assertTrue(Storage::disk('bmc_documents')->exists($real->file_path));
        $this->assertDatabaseMissing('document_versions', ['document_id' => $one->id]);
        $this->assertDatabaseMissing('document_scan_metadata', ['document_id' => $two->id]);

        $logs = ActivityLog::query()
            ->where('action', 'permanent_delete_document')
            ->orderBy('id')
            ->get();
        $this->assertCount(2, $logs);
        $this->assertSame(
            $logs[0]->metadata['bulk_operation_id'],
            $logs[1]->metadata['bulk_operation_id']
        );
        $this->assertNotEmpty($logs[0]->metadata['bulk_operation_id']);
        foreach ($logs as $log) {
            $this->assertNull($log->document_id);
            $this->assertSame('bulk_test_document_cleanup', $log->metadata['reason']);
            $this->assertTrue($log->metadata['was_active_at_deletion']);
            $this->assertSame('complete', $log->metadata['file_cleanup_status']);
        }
    }

    public function test_archived_item_mixed_with_active_rejects_whole_selection_before_deletion(): void
    {
        $active = $this->createDocument('Active dummy');
        $archived = $this->createDocument('Archived dummy', false);

        $this->actingAs($this->superAdmin)
            ->delete(route('documents.bulk-force-destroy'), $this->payload([$active->id, $archived->id]))
            ->assertSessionHasErrors('document_ids');

        $this->assertNotNull($active->fresh());
        $this->assertNotNull($archived->fresh());
        $this->assertTrue(Storage::disk('bmc_documents')->exists($active->file_path));
        $this->assertDatabaseMissing('activity_logs', ['action' => 'permanent_delete_document']);
    }

    public function test_missing_item_rejects_whole_selection_before_deletion(): void
    {
        $active = $this->createDocument('Active dummy');

        $this->actingAs($this->superAdmin)
            ->delete(route('documents.bulk-force-destroy'), $this->payload([$active->id, 999999]))
            ->assertSessionHasErrors('document_ids');

        $this->assertNotNull($active->fresh());
    }

    #[DataProvider('invalidConfirmationPayloads')]
    public function test_invalid_confirmation_never_deletes(array $overrides, string $key): void
    {
        $document = $this->createDocument('Important Record');

        $this->actingAs($this->superAdmin)
            ->delete(route('documents.bulk-force-destroy'), $this->payload([$document->id], $overrides))
            ->assertSessionHasErrors($key);

        $this->assertNotNull($document->fresh());
        $this->assertTrue(Storage::disk('bmc_documents')->exists($document->file_path));
    }

    public static function invalidConfirmationPayloads(): array
    {
        return [
            'lowercase' => [['confirmation' => 'hapus'], 'confirmation'],
            'wrong word' => [['confirmation' => 'HAPUS SEMUA'], 'confirmation'],
            'no acknowledgment' => [['acknowledge_dummy' => '0'], 'acknowledge_dummy'],
            'wrong count' => [['selected_count' => 2], 'document_ids'],
            'non-array' => [['document_ids' => '1'], 'document_ids'],
            'empty' => [['document_ids' => [], 'selected_count' => 0], 'document_ids'],
            'duplicates' => [['document_ids' => [1, 1], 'selected_count' => 2], 'document_ids.1'],
            'over limit' => [['document_ids' => range(1, 16), 'selected_count' => 16], 'document_ids'],
        ];
    }

    public function test_shared_file_is_preserved_if_unselected_document_references_it(): void
    {
        $sharedPath = 'documents/shared/test.pdf';
        $selected = $this->createDocument('Selected dummy', true, $sharedPath);
        $other = $this->createDocument('Real QC document', true, $sharedPath);

        $this->actingAs($this->superAdmin)
            ->delete(route('documents.bulk-force-destroy'), $this->payload([$selected->id]))
            ->assertSessionHas('success');

        $this->assertNull($selected->fresh());
        $this->assertNotNull($other->fresh());
        $this->assertTrue(Storage::disk('bmc_documents')->exists($sharedPath));

        $log = ActivityLog::query()->where('action', 'permanent_delete_document')->sole();
        $this->assertGreaterThanOrEqual(1, $log->metadata['preserved_shared_file_count']);
    }

    public function test_existing_single_document_permanent_delete_cannot_skip_archive(): void
    {
        $active = $this->createDocument('Active document');

        $this->actingAs($this->superAdmin)
            ->delete(route('documents.force-destroy', $active), ['confirmation' => 'HAPUS'])
            ->assertSessionHasErrors('document');

        $this->assertNotNull($active->fresh());
    }

    public function test_super_admin_sees_bulk_selection_interface_on_both_device_layouts(): void
    {
        $this->createDocument('Sample test record');

        $this->actingAs($this->superAdmin)
            ->get(route('documents.index'))
            ->assertOk()
            ->assertSee('data-qh-bulk-toolbar', false)
            ->assertSee('data-qh-bulk-document', false)
            ->assertSee('bulk-document-delete', false)
            ->assertSee('Hapus Permanen', false)
            ->assertSee('Pilih semua di halaman ini');
    }

    public function test_regular_admin_does_not_see_bulk_deletion_interface(): void
    {
        $document = $this->createDocument('Sample test record');
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('documents.index'))
            ->assertOk()
            ->assertDontSee('data-qh-bulk-toolbar', false)
            ->assertDontSee('data-qh-bulk-document', false)
            ->assertDontSee('bulk-document-delete', false);

        $this->assertNotNull($document->fresh());
    }

}
