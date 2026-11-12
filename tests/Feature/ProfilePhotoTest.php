<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function user(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'active',
            'must_change_password' => false,
        ]);
    }

    public function test_profile_has_professional_sections_and_photo_controls(): void
    {
        $this->actingAs($this->user())->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Profil Saya')
            ->assertSee('Informasi Akun')
            ->assertSee('Keamanan Kata Sandi')
            ->assertSee('Unggah Foto')
            ->assertDontSee('Hapus Akun');
    }

    public function test_signed_in_user_can_upload_and_view_own_photo(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post(route('profile.photo.store'), [
            'photo' => UploadedFile::fake()->image('avatar.png', 300, 300),
        ])->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'profile-photo-updated');

        $path = $user->fresh()->profile_photo_path;
        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);

        $this->actingAs($user)->get(route('profile.photo.show'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'private, no-store, max-age=0');
    }

    public function test_replacing_photo_removes_previous_file(): void
    {
        $user = $this->user();
        $this->actingAs($user)->post(route('profile.photo.store'), [
            'photo' => UploadedFile::fake()->image('first.jpg', 250, 250),
        ])->assertSessionHasNoErrors();

        $oldPath = $user->fresh()->profile_photo_path;

        $this->actingAs($user)->post(route('profile.photo.store'), [
            'photo' => UploadedFile::fake()->image('second.png', 300, 300),
        ])->assertSessionHasNoErrors();

        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($user->fresh()->profile_photo_path);
    }

    public function test_user_can_remove_photo(): void
    {
        $user = $this->user();
        $this->actingAs($user)->post(route('profile.photo.store'), [
            'photo' => UploadedFile::fake()->image('avatar.png', 250, 250),
        ])->assertSessionHasNoErrors();

        $path = $user->fresh()->profile_photo_path;

        $this->actingAs($user)->delete(route('profile.photo.destroy'))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'profile-photo-removed');

        $this->assertNull($user->fresh()->profile_photo_path);
        Storage::disk('local')->assertMissing($path);
        $this->actingAs($user)->get(route('profile.photo.show'))->assertNotFound();
    }

    public function test_invalid_or_large_files_are_rejected(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post(route('profile.photo.store'), [
            'photo' => UploadedFile::fake()->create('attack.svg', 1, 'image/svg+xml'),
        ])->assertSessionHasErrors('photo', null, 'profilePhoto');

        $this->actingAs($user)->post(route('profile.photo.store'), [
            'photo' => UploadedFile::fake()->image('too-big.png', 300, 300)->size(2050),
        ])->assertSessionHasErrors('photo', null, 'profilePhoto');

        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_guests_cannot_fetch_or_modify_photos_and_users_cannot_read_each_others(): void
    {
        $owner = $this->user();
        $other = $this->user();

        $this->get(route('profile.photo.show'))->assertRedirect(route('login'));
        $this->post(route('profile.photo.store'))->assertRedirect(route('login'));
        $this->delete(route('profile.photo.destroy'))->assertRedirect(route('login'));

        $this->actingAs($owner)->post(route('profile.photo.store'), [
            'photo' => UploadedFile::fake()->image('owner.png', 300, 300),
        ])->assertSessionHasNoErrors();

        $this->actingAs($other)->get(route('profile.photo.show'))->assertNotFound();
        $this->assertNotNull($owner->fresh()->profile_photo_path);
    }

    public function test_avatar_path_cannot_be_updated_through_profile_information_form(): void
    {
        $user = $this->user();
        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Safe Account',
            'email' => $user->email,
            'profile_photo_path' => 'profile-photos/999/fake.jpg',
            'role' => 'super_admin',
        ])->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_pending_password_change_keeps_account_gate(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'account_status' => 'active',
            'must_change_password' => true,
        ]);

        $this->actingAs($user)->post(route('profile.photo.store'), [
            'photo' => UploadedFile::fake()->image('avatar.png', 150, 150),
        ])->assertRedirect(route('profile.edit'));

        $this->assertNull($user->fresh()->profile_photo_path);
    }
}
