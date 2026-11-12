<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActivityLogAvatarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function makeUser(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'account_status' => 'active',
            'must_change_password' => false,
        ]);
    }

    private function uploadFakePhoto(User $user): string
    {
        $path = UploadedFile::fake()->image('avatar.jpg', 160, 160)->storeAs(
            'profile-photos/'.$user->id,
            '00000000-0000-4000-8000-000000000000.jpg',
            'local'
        );

        $user->forceFill(['profile_photo_path' => $path])->save();

        return $path;
    }

    private function logFor(?User $actor): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $actor?->id,
            'action' => 'view_document',
            'description' => 'Pengujian Activity Log',
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_super_admin_sees_photo_and_initial_fallback_in_activity_log(): void
    {
        $admin = $this->makeUser('super_admin');
        $withPhoto = $this->makeUser('user');
        $withoutPhoto = $this->makeUser('user');
        $withoutPhoto->forceFill(['name' => 'Budi QA'])->save();

        $this->uploadFakePhoto($withPhoto);
        $this->logFor($withPhoto);
        $this->logFor($withoutPhoto);
        $this->logFor(null);

        $response = $this->actingAs($admin)->get(route('activity-logs.index'))->assertOk();

        $response->assertSee(route('activity-logs.photo.show', $withPhoto), false)
            ->assertSee('alt="Foto profil '.e($withPhoto->name).'"', false)
            ->assertSee('Inisial Budi QA')
            ->assertSee('Aktivitas sistem')
            ->assertDontSee(route('activity-logs.photo.show', $withoutPhoto), false);
    }

    public function test_document_admin_can_view_private_audit_avatar_without_accessing_user_management(): void
    {
        $admin = $this->makeUser('admin');
        $actor = $this->makeUser('user');
        $this->uploadFakePhoto($actor);
        $this->logFor($actor);

        $this->actingAs($admin)
            ->get(route('activity-logs.index'))->assertOk()
            ->assertSee(route('activity-logs.photo.show', $actor), false);

        $response = $this->actingAs($admin)
            ->get(route('activity-logs.photo.show', $actor))->assertOk();

        $headers = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $headers);
        $this->assertStringContainsString('no-store', $headers);
        $this->assertStringNotContainsString('public', $headers);

        // The older User Management route remains restricted to Super Admin.
        $this->actingAs($admin)->get(route('users.photo.show', $actor))->assertForbidden();
        $this->actingAs($admin)->get(route('users.index'))->assertForbidden();
    }

    public function test_regular_employee_and_guest_cannot_fetch_audit_photos(): void
    {
        $actor = $this->makeUser('user');
        $this->uploadFakePhoto($actor);
        $this->logFor($actor);

        $this->get(route('activity-logs.photo.show', $actor))
            ->assertRedirect(route('login'));

        $employee = $this->makeUser('user');
        $this->actingAs($employee)
            ->get(route('activity-logs.photo.show', $actor))
            ->assertForbidden();
        $this->actingAs($employee)
            ->get(route('activity-logs.index'))
            ->assertForbidden();
    }

    public function test_missing_photo_and_wrong_owner_path_return_404(): void
    {
        $admin = $this->makeUser('super_admin');
        $actor = $this->makeUser('user');

        $this->actingAs($admin)
            ->get(route('activity-logs.photo.show', $actor))
            ->assertNotFound();

        $this->uploadFakePhoto($admin);
        $actor->forceFill([
            'profile_photo_path' => $admin->fresh()->profile_photo_path,
        ])->save();

        $this->actingAs($admin)
            ->get(route('activity-logs.photo.show', $actor))
            ->assertNotFound();
    }
}
