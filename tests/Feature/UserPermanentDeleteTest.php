<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentScanType;
use App\Models\DocumentVersion;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserPermanentDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
            'must_change_password' => false,
        ]);
    }

    private function employee(string $status = 'active', string $role = 'user'): User
    {
        return User::factory()->create([
            'role' => $role,
            'account_status' => $status,
            'must_change_password' => false,
        ]);
    }

    private function document(User $owner): Document
    {
        $department = Department::create([
            'name' => 'Quality Control',
            'code' => 'QC',
            'is_active' => true,
        ]);
        $category = Category::create([
            'department_id' => $department->id,
            'name' => 'Inspection',
            'slug' => 'inspection',
            'is_active' => true,
        ]);

        return Document::create([
            'category_id' => $category->id,
            'uploaded_by' => $owner->id,
            'archived_by' => $owner->id,
            'archived_at' => now(),
            'archive_reason' => 'Testing only',
            'title' => 'QC preserved report',
            'file_name' => 'report.pdf',
            'file_path' => 'documents/preserved/report.pdf',
            'file_extension' => 'pdf',
            'file_size' => 12,
            'is_active' => false,
            'current_revision' => 1,
        ]);
    }

    public static function permittedStatuses(): array
    {
        return [
            'active' => ['active'],
            'pending' => ['pending'],
            'inactive' => ['inactive'],
            'rejected' => ['rejected'],
        ];
    }

    #[DataProvider('permittedStatuses')]
    public function test_super_admin_can_permanently_delete_an_account_in_any_status(string $status): void
    {
        $admin = $this->superAdmin();
        $target = $this->employee($status);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $target), ['confirmation' => 'HAPUS'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'delete_user_permanently',
            'user_id' => $admin->id,
        ]);
    }

    public function test_deleting_used_admin_preserves_documents_revisions_and_audit(): void
    {
        // SessionSecurityService deliberately revokes persisted sessions
        // only when the configured session driver is database.
        config(['session.driver' => 'database']);

        $admin = $this->superAdmin();
        $target = $this->employee('active', 'admin');
        $document = $this->document($target);
        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'revision_number' => 1,
            'uploaded_by' => $target->id,
            'file_name' => 'revision.pdf',
            'file_path' => 'versions/preserved/revision.pdf',
            'file_extension' => 'pdf',
            'file_size' => 20,
        ]);
        $favorite = Favorite::create([
            'user_id' => $target->id,
            'document_id' => $document->id,
        ]);
        $existingLog = ActivityLog::create([
            'user_id' => $target->id,
            'document_id' => $document->id,
            'action' => 'view_document',
            'description' => 'Historical QC activity',
            'metadata' => ['source' => 'testing'],
        ]);
        $type = DocumentScanType::create([
            'name' => 'QC special inspection',
            'created_by' => $target->id,
            'is_active' => true,
        ]);

        Storage::disk('local')->put('documents/preserved/report.pdf', 'original file');
        Storage::disk('local')->put('versions/preserved/revision.pdf', 'revision file');
        $photo = UploadedFile::fake()->image('avatar.jpg', 160, 160);
        $photoPath = $photo->storeAs(
            'profile-photos/'.$target->id,
            '00000000-0000-4000-8000-000000000000.jpg',
            'local'
        );
        $target->forceFill(['profile_photo_path' => $photoPath])->save();

        DB::table('sessions')->insert([
            'id' => 'dummy-logged-in-session',
            'user_id' => $target->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test browser',
            'payload' => '',
            'last_activity' => time(),
        ]);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $target), ['confirmation' => 'HAPUS'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('favorites', ['id' => $favorite->id]);
        $this->assertDatabaseMissing('sessions', ['id' => 'dummy-logged-in-session']);
        $this->assertFalse(Storage::disk('local')->exists($photoPath));

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'uploaded_by' => null,
            'archived_by' => null,
        ]);
        $this->assertDatabaseHas('document_versions', [
            'id' => $version->id,
            'uploaded_by' => null,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'id' => $existingLog->id,
            'user_id' => null,
            'document_id' => $document->id,
        ]);
        $this->assertDatabaseHas('document_scan_types', [
            'id' => $type->id,
            'created_by' => null,
        ]);
        $this->assertTrue(Storage::disk('local')->exists('documents/preserved/report.pdf'));
        $this->assertTrue(Storage::disk('local')->exists('versions/preserved/revision.pdf'));

        $audit = ActivityLog::where('action', 'delete_user_permanently')->firstOrFail();
        $this->assertSame($target->id, $audit->metadata['target_user_id']);
        $this->assertSame('admin', $audit->metadata['target_role']);
        $this->assertSame('active', $audit->metadata['before_status']);
    }

    #[DataProvider('unauthorizedRoles')]
    public function test_other_roles_cannot_delete_accounts(string $role): void
    {
        $target = $this->employee();
        $actor = $this->employee('active', $role);

        $this->actingAs($actor)
            ->delete(route('users.destroy', $target), ['confirmation' => 'HAPUS'])
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public static function unauthorizedRoles(): array
    {
        return ['admin' => ['admin'], 'employee' => ['user']];
    }

    public function test_super_admin_and_current_account_remain_protected(): void
    {
        $admin = $this->superAdmin();
        $otherSuperAdmin = $this->superAdmin();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $otherSuperAdmin), ['confirmation' => 'HAPUS'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin), ['confirmation' => 'HAPUS'])
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('users', ['id' => $otherSuperAdmin->id]);
    }

    public function test_wrong_or_missing_confirmation_does_not_delete_anyone(): void
    {
        $admin = $this->superAdmin();
        $target = $this->employee();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $target), ['confirmation' => 'HAPUS PERMANEN'])
            ->assertSessionHasErrors('confirmation');

        $this->actingAs($admin)
            ->delete(route('users.destroy', $target), [])
            ->assertSessionHasErrors('confirmation');

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_ui_shows_delete_for_active_and_pending_accounts_but_not_super_admin(): void
    {
        $admin = $this->superAdmin();
        $active = $this->employee('active');
        $pending = $this->employee('pending');
        $inactive = $this->employee('inactive');

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('delete-user-'.$active->id, false)
            ->assertSee('delete-user-'.$pending->id, false)
            ->assertSee('delete-user-'.$inactive->id, false)
            ->assertDontSee('delete-user-'.$admin->id, false);
    }

    public function test_user_management_renders_validation_errors(): void
    {
        $admin = $this->superAdmin();
        $target = $this->employee();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $target), ['confirmation' => 'WRONG'])
            ->assertSessionHasErrors('confirmation');

        $this->get(route('users.index'))
            ->assertOk()
            ->assertSee('Ketik HAPUS untuk mengonfirmasi penghapusan permanen.');
    }
}
