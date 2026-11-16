<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BulkDocumentArchiveTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Category $category;
    private string $storageRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageRoot = storage_path('framework/testing/bulk-archive-'.bin2hex(random_bytes(8)));
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

    private function document(string $title, bool $active = true): Document
    {
        $path = 'documents/'.bin2hex(random_bytes(8)).'/original.pdf';
        $document = Document::create([
            'category_id' => $this->category->id,
            'uploaded_by' => $this->superAdmin->id,
            'title' => $title,
            'document_code' => 'QC-'.bin2hex(random_bytes(3)),
            'file_name' => 'original.pdf',
            'file_path' => $path,
            'file_extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 20,
            'is_active' => $active,
            'archived_at' => $active ? null : now(),
            'archived_by' => $active ? null : $this->superAdmin->id,
            'archive_reason' => $active ? null : 'Sudah lama',
            'current_revision' => 1,
        ]);

        Storage::disk('bmc_documents')->put($path, 'original content');
        return $document;
    }

    private function payload(array $ids, array $overrides = []): array
    {
        return array_merge([
            'document_ids' => $ids,
            'selected_count' => count($ids),
            'archive_reason' => 'Pemeriksaan QC sudah selesai.',
        ], $overrides);
    }

    public function test_guest_is_redirected_and_documents_remain_active(): void
    {
        $document = $this->document('QC report');

        $this->patch(route('documents.bulk-archive'), $this->payload([$document->id]))
            ->assertRedirect(route('login'));

        $this->assertTrue($document->fresh()->is_active);
        $this->assertTrue(Storage::disk('bmc_documents')->exists($document->file_path));
    }

    #[DataProvider('readOnlyRoles')]
    public function test_user_and_viewer_cannot_archive_any_document(string $role): void
    {
        $document = $this->document('QC report');
        $actor = User::factory()->create([
            'role' => $role,
            'account_status' => 'active',
        ]);

        $this->actingAs($actor)
            ->patch(route('documents.bulk-archive'), $this->payload([$document->id]))
            ->assertForbidden();

        $this->assertTrue($document->fresh()->is_active);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'archive_document']);
    }

    public static function readOnlyRoles(): array
    {
        return ['user' => ['user'], 'viewer' => ['viewer']];
    }

    #[DataProvider('archiveAuthorizedRoles')]
    public function test_admin_and_super_admin_can_archive_multiple_documents_atomically(string $role): void
    {
        $actor = $role === 'super_admin'
            ? $this->superAdmin
            : User::factory()->create([
                'role' => 'admin',
                'account_status' => 'active',
            ]);

        $first = $this->document('QC dummy A');
        $second = $this->document('QC dummy B');
        $untouched = $this->document('QC production');
        $oldVersionPath = 'versions/'.bin2hex(random_bytes(8)).'/original.pdf';
        Storage::disk('bmc_documents')->put($oldVersionPath, 'previous revision');

        $version = DocumentVersion::create([
            'document_id' => $first->id,
            'revision_number' => 0,
            'uploaded_by' => $this->superAdmin->id,
            'file_name' => 'previous.pdf',
            'file_path' => $oldVersionPath,
            'file_extension' => 'pdf',
            'file_size' => 17,
        ]);

        $this->actingAs($actor)
            ->patch(
                route('documents.bulk-archive'),
                $this->payload([$first->id, $second->id])
            )
            ->assertRedirect(route('documents.archived'))
            ->assertSessionHas(
                'success',
                '2 dokumen terpilih berhasil diarsipkan. Seluruh file dan revisi tetap tersimpan.'
            );

        foreach ([$first, $second] as $document) {
            $fresh = $document->fresh();
            $this->assertFalse($fresh->is_active);
            $this->assertSame($actor->id, $fresh->archived_by);
            $this->assertSame('Pemeriksaan QC sudah selesai.', $fresh->archive_reason);
            $this->assertNotNull($fresh->archived_at);
            $this->assertTrue(Storage::disk('bmc_documents')->exists($fresh->file_path));
        }

        $this->assertTrue($untouched->fresh()->is_active);
        $this->assertTrue(Storage::disk('bmc_documents')->exists($untouched->file_path));
        $this->assertNotNull($version->fresh());
        $this->assertTrue(Storage::disk('bmc_documents')->exists($oldVersionPath));

        $logs = ActivityLog::query()
            ->where('action', 'archive_document')
            ->orderBy('id')
            ->get();
        $this->assertCount(2, $logs);
        $this->assertSame(
            $logs[0]->metadata['bulk_operation_id'],
            $logs[1]->metadata['bulk_operation_id']
        );
        $this->assertNotEmpty($logs[0]->metadata['bulk_operation_id']);
        foreach ($logs as $log) {
            $this->assertSame($actor->id, $log->user_id);
            $this->assertSame('archive', $log->metadata['bulk_action']);
            $this->assertFalse($log->metadata['physical_file_deleted']);
            $this->assertFalse($log->metadata['revision_history_deleted']);
            $this->assertSame('Pemeriksaan QC sudah selesai.', $log->metadata['archive_reason']);
        }
    }

    public static function archiveAuthorizedRoles(): array
    {
        return ['admin' => ['admin'], 'super_admin' => ['super_admin']];
    }

    public function test_archived_item_mixed_with_active_rejects_whole_batch(): void
    {
        $active = $this->document('Active QC');
        $archived = $this->document('Already archived QC', false);

        $this->actingAs($this->superAdmin)
            ->patch(route('documents.bulk-archive'), $this->payload([$active->id, $archived->id]))
            ->assertSessionHasErrors('document_ids');

        $this->assertTrue($active->fresh()->is_active);
        $this->assertFalse($archived->fresh()->is_active);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_missing_item_mixed_with_active_rejects_whole_batch(): void
    {
        $active = $this->document('Active QC');

        $this->actingAs($this->superAdmin)
            ->patch(route('documents.bulk-archive'), $this->payload([$active->id, 999999]))
            ->assertSessionHasErrors('document_ids');

        $this->assertTrue($active->fresh()->is_active);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    #[DataProvider('invalidBatchInputs')]
    public function test_invalid_inputs_reject_without_archiving(array $overrides, string $errorField): void
    {
        $document = $this->document('Active QC');

        $this->actingAs($this->superAdmin)
            ->patch(route('documents.bulk-archive'), $this->payload([$document->id], $overrides))
            ->assertSessionHasErrors($errorField);

        $this->assertTrue($document->fresh()->is_active);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertTrue(Storage::disk('bmc_documents')->exists($document->file_path));
    }

    public static function invalidBatchInputs(): array
    {
        return [
            'empty selection' => [['document_ids' => [], 'selected_count' => 0], 'document_ids'],
            'nonarray ids' => [['document_ids' => '1'], 'document_ids'],
            'duplicate ids' => [['document_ids' => [1, 1], 'selected_count' => 2], 'document_ids.1'],
            'too many ids' => [['document_ids' => range(1, 16), 'selected_count' => 16], 'document_ids'],
            'count mismatch' => [['selected_count' => 2], 'document_ids'],
            'empty reason' => [['archive_reason' => ''], 'archive_reason'],
            'short reason' => [['archive_reason' => 'ab'], 'archive_reason'],
            'long reason' => [['archive_reason' => str_repeat('a', 1001)], 'archive_reason'],
        ];
    }

    public function test_archive_reason_is_sanitized_as_plain_text(): void
    {
        $document = $this->document('QC record');

        $this->actingAs($this->superAdmin)
            ->patch(route('documents.bulk-archive'), $this->payload([$document->id], [
                'archive_reason' => '<b>Dokumen lama</b>',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('Dokumen lama', $document->fresh()->archive_reason);
    }

    public function test_admin_sees_archive_controls_but_not_permanent_delete(): void
    {
        $this->document('QC Archive candidate');
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('documents.index'))
            ->assertOk()
            ->assertSee('data-qh-bulk-toolbar', false)
            ->assertSee('data-qh-bulk-document', false)
            ->assertSee('bulk-document-archive', false)
            ->assertSee('Arsipkan', false)
            ->assertDontSee('bulk-document-delete', false)
            ->assertDontSee('Hapus Permanen (', false);
    }

    public function test_super_admin_sees_both_archive_and_permanent_delete(): void
    {
        $this->document('QC Archive candidate');

        $this->actingAs($this->superAdmin)
            ->get(route('documents.index'))
            ->assertOk()
            ->assertSee('bulk-document-archive', false)
            ->assertSee('bulk-document-delete', false)
            ->assertSee('data-qh-bulk-select-all', false);
    }

    public function test_user_has_no_bulk_archive_toolbar_or_checkbox(): void
    {
        $this->document('QC record');
        $user = User::factory()->create([
            'role' => 'user',
            'account_status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('documents.index'))
            ->assertOk()
            ->assertDontSee('data-qh-bulk-toolbar', false)
            ->assertDontSee('data-qh-bulk-document', false)
            ->assertDontSee('bulk-document-archive', false);
    }
}
