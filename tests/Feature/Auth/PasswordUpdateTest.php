<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_PASSWORD =
        'NewQualityHub#2026Pass';

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(
            Hash::check(
                self::NEW_PASSWORD,
                $user->refresh()->password
            )
        );
    }

    public function test_user_can_choose_simple_eight_character_password_in_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'abcdefgh',
                'password_confirmation' => 'abcdefgh',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('abcdefgh', $user->fresh()->password));
    }

    public function test_profile_rejects_password_shorter_than_eight_characters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'abcdefg',
                'password_confirmation' => 'abcdefg',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'password')
            ->assertRedirect('/profile');
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ]);

        $response
            ->assertSessionHasErrorsIn(
                'updatePassword',
                'current_password'
            )
            ->assertRedirect('/profile');
    }
}
