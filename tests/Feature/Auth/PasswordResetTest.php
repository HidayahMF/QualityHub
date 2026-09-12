<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_PASSWORD =
        'ResetQualityHub#2026Pass';

    protected function setUp(): void
    {
        parent::setUp();

        /*
        | PHPUnit default project memakai SESSION_DRIVER=array.
        | Test reset password ini sengaja memverifikasi pencabutan row
        | session database, jadi driver harus disamakan dengan konfigurasi
        | aplikasi BMC QualityHub yang memakai database session.
        */
        config([
            'session.driver' => 'database',
        ]);
    }

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo(
            $user,
            ResetPassword::class
        );
    }

    public function test_reset_request_does_not_reveal_unknown_account(): void
    {
        Notification::fake();

        $response = $this->post('/forgot-password', [
            'email' => 'unknown@example.com',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas(
                'status',
                'Jika alamat email terdaftar, tautan pengaturan ulang kata sandi akan dikirim.'
            );
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function ($notification) {
                $response = $this->get(
                    '/reset-password/'
                    . $notification->token
                );

                $response->assertStatus(200);

                return true;
            }
        );
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'remember_token' => 'remember-before-reset',
        ]);

        $oldRememberToken = $user->remember_token;

        DB::table('sessions')->insert([
            'id' => 'reset-old-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => time(),
        ]);

        $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function ($notification) use ($user, $oldRememberToken) {
                $response = $this->post(
                    '/reset-password',
                    [
                        'token' => $notification->token,
                        'email' => $user->email,
                        'password' => self::NEW_PASSWORD,
                        'password_confirmation' => self::NEW_PASSWORD,
                    ]
                );

                $response
                    ->assertSessionHasNoErrors()
                    ->assertRedirect(route('login'));

                $user->refresh();

                $this->assertNotSame(
                    $oldRememberToken,
                    $user->remember_token
                );

                $this->assertDatabaseMissing(
                    'sessions',
                    [
                        'id' => 'reset-old-session',
                    ]
                );

                return true;
            }
        );
    }
}
