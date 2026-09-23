<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create([
            'name' => 'BMC Administrator',
            'email' => 'admin@bmc.local',
            'role' => 'super_admin',
            'account_status' => 'active',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response
            ->assertOk()
            ->assertSee('My Profile')
            ->assertSee(
                'Kelola informasi akun dan keamanan akses BMC QualityHub.'
            )
            ->assertSee('BMC Administrator')
            ->assertSee('admin@bmc.local')
            ->assertSee('Super Admin')
            ->assertSee('Aktif')
            ->assertDontSee('Hapus Akun')
            ->assertDontSee('confirm-user-deletion');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_profile_does_not_render_delete_account_modal(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response
            ->assertOk()
            ->assertDontSee('Hapus Akun')
            ->assertDontSee('confirm-user-deletion')
            ->assertDontSee('data-qh-modal-show="true"', false);
    }

    public function test_modal_component_is_closed_by_default_and_uses_csp_provider(): void
    {
        $html = Blade::render(
            '<x-modal name="profile-audit"><p>Modal audit</p></x-modal>'
        );

        $this->assertStringContainsString(
            'data-qh-modal-show="false"',
            $html
        );
        $this->assertStringContainsString(
            'x-data="qualityHubModal"',
            $html
        );
        $this->assertStringContainsString('x-cloak', $html);

        $source = (string) file_get_contents(
            resource_path('views/components/modal.blade.php')
        );
        $appJs = (string) file_get_contents(
            resource_path('js/app.js')
        );

        $this->assertStringNotContainsString(
            'focusables() {',
            $source
        );
        $this->assertStringContainsString(
            "Alpine.data('qualityHubModal'",
            $appJs
        );
    }

    public function test_self_delete_route_is_disabled(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(Route::has('profile.destroy'));

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response->assertStatus(405);

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh());
    }
}
