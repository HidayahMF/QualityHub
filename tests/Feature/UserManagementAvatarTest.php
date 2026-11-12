<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserManagementAvatarTest extends TestCase
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

    private function employee(): User
    {
        return User::factory()->create([
            'role' => 'user',
            'account_status' => 'active',
            'must_change_password' => false,
        ]);
    }

    private function attachPhoto(User $user): string
    {
        $file = UploadedFile::fake()->image('photo.jpg', 150, 150);
        $path = $file->storeAs(
            'profile-photos/'.$user->id,
            '00000000-0000-4000-8000-000000000000.jpg',
            'local'
        );

        $user->forceFill(['profile_photo_path' => $path])->save();

        return $path;
    }

    public function test_super_admin_sees_user_photo_and_initial_fallback_in_users_table(): void
    {
        $admin = $this->superAdmin();
        $withPhoto = $this->employee();
        $withoutPhoto = $this->employee();
        $withoutPhoto->forceFill(['name' => 'Budi QA'])->save();
        $this->attachPhoto($withPhoto);

        $response = $this->actingAs($admin)->get(route('users.index'))->assertOk();

        $response->assertSee(route('users.photo.show', $withPhoto), false)
            ->assertSee('alt="Foto profil '.e($withPhoto->name).'"', false)
            ->assertSee('Inisial Budi QA')
            ->assertDontSee(route('users.photo.show', $withoutPhoto), false);
    }

    public function test_super_admin_can_read_managed_users_private_avatar(): void
    {
        $admin = $this->superAdmin();
        $employee = $this->employee();
        $this->attachPhoto($employee);

        $response = $this->actingAs($admin)
            ->get(route('users.photo.show', $employee))
            ->assertOk();

        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringNotContainsString('public', $cacheControl);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_photo_route_rejects_guest_and_regular_employee(): void
    {
        $employee = $this->employee();
        $this->attachPhoto($employee);

        $this->get(route('users.photo.show', $employee))
            ->assertRedirect(route('login'));

        $otherEmployee = $this->employee();
        $this->actingAs($otherEmployee)
            ->get(route('users.photo.show', $employee))
            ->assertForbidden();

        // The account list itself must stay restricted as before.
        $this->actingAs($otherEmployee)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_missing_and_unowned_photo_paths_return_not_found(): void
    {
        $admin = $this->superAdmin();
        $employee = $this->employee();

        $this->actingAs($admin)
            ->get(route('users.photo.show', $employee))
            ->assertNotFound();

        // A corrupted path cannot cause an arbitrary user's file to be served.
        $this->attachPhoto($admin);
        $employee->forceFill([
            'profile_photo_path' => $admin->fresh()->profile_photo_path,
        ])->save();

        $this->actingAs($admin)
            ->get(route('users.photo.show', $employee))
            ->assertNotFound();
    }

    public function test_photo_route_requires_fresh_session_and_preserves_existing_account_gates(): void
    {
        $admin = $this->superAdmin();
        $employee = $this->employee();
        $this->attachPhoto($employee);

        $this->actingAs($admin)->get(route('users.photo.show', $employee))->assertOk();

        // A switched identity on the same session must be revoked, not served.
        $other = $this->employee();
        $this->actingAs($other)->get(route('users.photo.show', $employee))
            ->assertRedirect(route('login'));
    }
}
