<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->withSession(['logout_probe' => 'must-be-invalidated'])
            ->post('/logout');

        $this->assertGuest();
        $response
            ->assertRedirect(route('login'))
            ->assertSessionMissing('logout_probe');
    }

    public function test_logout_does_not_accept_get_requests(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/logout')
            ->assertMethodNotAllowed();

        $this->assertAuthenticatedAs($user);
    }

    public function test_main_and_legacy_navigation_render_only_post_logout_forms(): void
    {
        $user = User::factory()->create();
        $logoutUrl = route('logout');

        $mainLayout = $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();

        $mainLayout
            ->assertSee('method="POST"', false)
            ->assertSee('action="'.$logoutUrl.'"', false)
            ->assertSee('name="_token"', false)
            ->assertDontSee('href="'.$logoutUrl.'"', false)
            ->assertDontSee('data-submit-form', false);

        $legacyNavigation = $this->view('layouts.navigation');
        $legacyNavigation
            ->assertSee('method="POST"', false)
            ->assertSee('action="'.$logoutUrl.'"', false)
            ->assertSee('name="_token"', false)
            ->assertDontSee('href="'.$logoutUrl.'"', false)
            ->assertDontSee('data-submit-form', false);

        $this->assertSame(
            2,
            substr_count(
                (string) $legacyNavigation,
                'action="'.$logoutUrl.'"'
            )
        );
    }
}
