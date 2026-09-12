<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SessionSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthSessionHardeningTest extends TestCase
{
    use RefreshDatabase;

    private const STRONG_PASSWORD =
        'QualityHub#2026Session';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'session.driver' => 'database',
            'smartfind_security.auth.remember_login_enabled' => false,
            'smartfind_security.auth.login_max_attempts' => 5,
            'smartfind_security.auth.login_decay_seconds' => 300,
            'smartfind_security.auth.login_ip_max_attempts' => 20,
            'smartfind_security.auth.login_ip_decay_seconds' => 300,
            'smartfind_security.session.idle_timeout_minutes' => 60,
            'smartfind_security.session.absolute_timeout_minutes' => 720,
            'smartfind_security.session.max_concurrent_sessions' => 3,
            'smartfind_security.session.password_fingerprint_enabled' => true,
        ]);
    }

    public function test_login_normalizes_email_initializes_session_and_ignores_remember_when_disabled(): void
    {
        $user = User::factory()->create([
            'email' => 'quality.user@example.com',
            'account_status' => 'active',
            'remember_token' => 'remember-token-before-login',
        ]);

        $oldRememberToken = $user->remember_token;

        $response = $this->post('/login', [
            'email' => '  QUALITY.USER@EXAMPLE.COM  ',
            'password' => 'password',
            'remember' => '1',
        ]);

        $this->assertAuthenticatedAs($user);

        $response
            ->assertRedirect(
                route('dashboard', absolute: false)
            )
            ->assertSessionHas(
                SessionSecurityService::USER_ID_KEY,
                $user->id
            )
            ->assertSessionHas(
                SessionSecurityService::AUTHENTICATED_AT_KEY
            )
            ->assertSessionHas(
                SessionSecurityService::LAST_ACTIVITY_KEY
            )
            ->assertSessionHas(
                SessionSecurityService::PASSWORD_FINGERPRINT_KEY
            );

        $this->assertSame(
            $oldRememberToken,
            $user->refresh()->remember_token
        );
    }

    public function test_repeated_invalid_login_is_rate_limited(): void
    {
        $user = User::factory()->create([
            'email' => 'rate.limit@example.com',
            'account_status' => 'active',
        ]);

        $accountKey = 'qualityhub-login-account:'
            . hash(
                'sha256',
                $user->email.'|127.0.0.1'
            );

        $ipKey = 'qualityhub-login-ip:'
            . hash(
                'sha256',
                '127.0.0.1'
            );

        RateLimiter::clear($accountKey);
        RateLimiter::clear($ipKey);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertGreaterThan(
            0,
            RateLimiter::availableIn($accountKey)
        );

        RateLimiter::clear($accountKey);
        RateLimiter::clear($ipKey);
    }

    public function test_absolute_session_timeout_revokes_authenticated_session(): void
    {
        config([
            'smartfind_security.session.absolute_timeout_minutes' => 1,
        ]);

        $user = User::factory()->create([
            'account_status' => 'active',
        ]);

        $fingerprint = app(
            SessionSecurityService::class
        )->passwordFingerprint($user);

        $response = $this
            ->actingAs($user)
            ->withSession([
                SessionSecurityService::USER_ID_KEY => $user->id,
                SessionSecurityService::AUTHENTICATED_AT_KEY => time() - 120,
                SessionSecurityService::LAST_ACTIVITY_KEY => time(),
                SessionSecurityService::PASSWORD_FINGERPRINT_KEY => $fingerprint,
            ])
            ->get(route('dashboard'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_password_fingerprint_mismatch_revokes_authenticated_session(): void
    {
        $user = User::factory()->create([
            'account_status' => 'active',
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession([
                SessionSecurityService::USER_ID_KEY => $user->id,
                SessionSecurityService::AUTHENTICATED_AT_KEY => time(),
                SessionSecurityService::LAST_ACTIVITY_KEY => time(),
                SessionSecurityService::PASSWORD_FINGERPRINT_KEY => str_repeat('0', 64),
            ])
            ->get(route('dashboard'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_password_update_rotates_remember_token_and_revokes_other_database_sessions(): void
    {
        $user = User::factory()->create([
            'account_status' => 'active',
            'remember_token' => 'remember-token-before-password-change',
        ]);

        $oldRememberToken = $user->remember_token;

        $this->insertSession(
            'old-device-session',
            $user->id
        );

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => self::STRONG_PASSWORD,
                'password_confirmation' => self::STRONG_PASSWORD,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertTrue(
            Hash::check(
                self::STRONG_PASSWORD,
                $user->password
            )
        );

        $this->assertNotSame(
            $oldRememberToken,
            $user->remember_token
        );

        $this->assertDatabaseMissing(
            'sessions',
            [
                'id' => 'old-device-session',
            ]
        );
    }

    public function test_admin_deactivation_immediately_revokes_target_sessions_and_remember_token(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        $target = User::factory()->create([
            'role' => 'user',
            'account_status' => 'active',
            'remember_token' => 'target-remember-token-before-deactivate',
        ]);

        $oldRememberToken = $target->remember_token;

        $this->insertSession(
            'target-old-session',
            $target->id
        );

        $response = $this
            ->actingAs($admin)
            ->from(route('users.index'))
            ->patch(
                route(
                    'users.deactivate',
                    $target
                )
            );

        $response->assertRedirect(route('users.index'));

        $target->refresh();

        $this->assertSame(
            'inactive',
            $target->account_status
        );

        $this->assertNotSame(
            $oldRememberToken,
            $target->remember_token
        );

        $this->assertDatabaseMissing(
            'sessions',
            [
                'id' => 'target-old-session',
            ]
        );
    }

    private function insertSession(
        string $sessionId,
        int $userId
    ): void {
        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => time(),
        ]);
    }
}
