<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_directs_employee_to_super_admin(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Hubungi Super Admin');
    }

    public function test_anonymous_email_password_reset_is_disabled(): void
    {
        $this->post('/forgot-password', [
            'email' => 'user@example.test',
        ])->assertMethodNotAllowed();

        $this->get('/reset-password/some-token')->assertNotFound();
    }

    public function test_authenticated_user_can_change_own_password(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
        ]);

        $this->actingAs($user)
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password' => 'Fresh#Quality2026Pass',
                'password_confirmation' => 'Fresh#Quality2026Pass',
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertTrue(Hash::check('Fresh#Quality2026Pass', $user->password));
        $this->assertFalse($user->must_change_password);
    }
}
