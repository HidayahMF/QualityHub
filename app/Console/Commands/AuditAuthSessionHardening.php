<?php

namespace App\Console\Commands;

use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureSessionFreshness;
use App\Services\SessionSecurityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class AuditAuthSessionHardening extends Command
{
    protected $signature =
        'smartfind:audit-auth-session';

    protected $description =
        'Audit read-only authentication, password policy, rate limiting, and authenticated session hardening.';

    public function handle(): int
    {
        $checks = [];

        $environment = (string) app()->environment();
        $isProduction = $environment === 'production';

        $loginAttempts = (int) config(
            'smartfind_security.auth.login_max_attempts',
            0
        );

        $loginDecay = (int) config(
            'smartfind_security.auth.login_decay_seconds',
            0
        );

        $ipAttempts = (int) config(
            'smartfind_security.auth.login_ip_max_attempts',
            0
        );

        $ipDecay = (int) config(
            'smartfind_security.auth.login_ip_decay_seconds',
            0
        );

        $passwordMin = (int) config(
            'smartfind_security.auth.password_min_length',
            0
        );

        $rememberEnabled = config(
            'smartfind_security.auth.remember_login_enabled',
            false
        ) === true;

        $idleTimeout = (int) config(
            'smartfind_security.session.idle_timeout_minutes',
            0
        );

        $absoluteTimeout = (int) config(
            'smartfind_security.session.absolute_timeout_minutes',
            0
        );

        $maxConcurrent = (int) config(
            'smartfind_security.session.max_concurrent_sessions',
            0
        );

        $fingerprintEnabled = config(
            'smartfind_security.session.password_fingerprint_enabled',
            false
        ) === true;

        $this->check(
            $checks,
            'Login limiter per akun + IP',
            $loginAttempts > 0
                && $loginAttempts <= 5
                && $loginDecay >= 300,
            'ERROR',
            "{$loginAttempts} percobaan / {$loginDecay} detik."
        );

        $this->check(
            $checks,
            'Login limiter credential spraying per IP',
            $ipAttempts > 0
                && $ipAttempts <= 20
                && $ipDecay >= 300,
            'ERROR',
            "{$ipAttempts} percobaan / {$ipDecay} detik."
        );

        $this->check(
            $checks,
            'Persistent Remember Me disabled',
            ! $rememberEnabled,
            'WARNING',
            $rememberEnabled
                ? 'Remember Me aktif. Untuk aplikasi internal perusahaan disarankan OFF.'
                : 'Login persisten dinonaktifkan.'
        );

        $this->check(
            $checks,
            'Password minimum 12 karakter',
            $passwordMin >= 12,
            'ERROR',
            'Minimum saat ini: '.$passwordMin.' karakter.'
        );

        $this->check(
            $checks,
            'Session driver database',
            strtolower((string) config('session.driver')) === 'database',
            'WARNING',
            'Driver saat ini: '.(string) config('session.driver')
        );

        $this->check(
            $checks,
            'Session encryption',
            config('session.encrypt') === true,
            'ERROR',
            'SESSION_ENCRYPT=true wajib untuk baseline QualityHub.'
        );

        $this->check(
            $checks,
            'Session cookie HttpOnly',
            config('session.http_only') === true,
            'ERROR',
            'SESSION_HTTP_ONLY=true wajib.'
        );

        $sameSite = strtolower(
            (string) config('session.same_site', '')
        );

        $this->check(
            $checks,
            'Session cookie SameSite',
            in_array(
                $sameSite,
                ['lax', 'strict'],
                true
            ),
            'ERROR',
            'Saat ini: '.($sameSite !== '' ? $sameSite : '[null]')
        );

        $this->check(
            $checks,
            'Secure cookie production',
            ! $isProduction
                || config('session.secure') === true,
            $isProduction ? 'ERROR' : 'INFO',
            $isProduction
                ? 'Production HTTPS wajib SESSION_SECURE_COOKIE=true.'
                : 'Local HTTP boleh SESSION_SECURE_COOKIE=false.'
        );

        $this->check(
            $checks,
            'Idle session timeout',
            $idleTimeout > 0
                && $idleTimeout <= 60,
            'WARNING',
            'Timeout saat ini: '.$idleTimeout.' menit.'
        );

        $this->check(
            $checks,
            'Absolute session timeout',
            $absoluteTimeout > 0
                && $absoluteTimeout <= 1440
                && (
                    $idleTimeout === 0
                    || $absoluteTimeout >= $idleTimeout
                ),
            'ERROR',
            'Absolute timeout: '.$absoluteTimeout.' menit.'
        );

        $this->check(
            $checks,
            'Concurrent session limit',
            $maxConcurrent > 0
                && $maxConcurrent <= 3,
            'WARNING',
            'Maksimum sesi aktif per user: '.$maxConcurrent.'.'
        );

        $this->check(
            $checks,
            'Password-change session fingerprint',
            $fingerprintEnabled,
            'ERROR',
            'Session lama otomatis invalid jika password hash berubah.'
        );

        $this->check(
            $checks,
            'SessionSecurityService tersedia',
            class_exists(SessionSecurityService::class),
            'ERROR'
        );

        $dashboardRoute = Route::getRoutes()
            ->getByName('dashboard');

        $dashboardMiddleware = $dashboardRoute !== null
            ? $dashboardRoute->gatherMiddleware()
            : [];

        $this->check(
            $checks,
            'EnsureSessionFreshness melindungi internal route',
            in_array(
                EnsureSessionFreshness::class,
                $dashboardMiddleware,
                true
            ),
            'ERROR'
        );

        $passwordRoute = Route::getRoutes()
            ->getByName('password.update');

        $passwordMiddleware = $passwordRoute !== null
            ? $passwordRoute->gatherMiddleware()
            : [];

        $this->check(
            $checks,
            'Auth-sensitive route memeriksa akun aktif',
            in_array(
                EnsureActiveAccount::class,
                $passwordMiddleware,
                true
            ),
            'ERROR',
            'Password update tidak boleh berjalan dari akun yang sudah dinonaktifkan.'
        );

        $this->check(
            $checks,
            'Auth-sensitive route memeriksa session freshness',
            in_array(
                EnsureSessionFreshness::class,
                $passwordMiddleware,
                true
            ),
            'ERROR',
            'Password update harus melewati idle/absolute/fingerprint session checks.'
        );

        foreach (
            [
                'register.store' => 'Registration throttled',
                'password.email' => 'Forgot password throttled',
                'password.store' => 'Reset password throttled',
                'password.update' => 'Password update throttled',
                'logout' => 'Logout throttled',
            ] as $routeName => $label
        ) {
            $route = Route::getRoutes()
                ->getByName($routeName);

            $middleware = $route !== null
                ? $route->gatherMiddleware()
                : [];

            $hasThrottle = collect($middleware)
                ->contains(
                    fn ($item) =>
                        is_string($item)
                        && str_starts_with(
                            $item,
                            'throttle:'
                        )
                );

            $this->check(
                $checks,
                $label,
                $hasThrottle,
                'ERROR'
            );
        }

        $rows = array_map(
            fn (array $check) => [
                $check['status'],
                $check['check'],
                $check['message'],
            ],
            $checks
        );

        $this->table(
            [
                'Status',
                'Check',
                'Catatan',
            ],
            $rows
        );

        $errors = collect($checks)
            ->where('status', 'FAIL')
            ->count();

        $warnings = collect($checks)
            ->where('status', 'WARN')
            ->count();

        $this->newLine();

        if ($errors === 0) {
            $this->info(
                'AUTH & SESSION HARDENING AUDIT: PASS'
            );
        } else {
            $this->error(
                'AUTH & SESSION HARDENING AUDIT: FAIL'
            );
        }

        $this->line(
            'Critical Fail: '
            . $errors
            . ' | Warning: '
            . $warnings
        );

        return $errors === 0
            ? self::SUCCESS
            : self::FAILURE;
    }

    private function check(
        array &$checks,
        string $name,
        bool $passed,
        string $severity,
        string $message = ''
    ): void {
        if ($severity === 'INFO') {
            $status = 'INFO';
        } elseif ($severity === 'WARNING') {
            $status = $passed
                ? 'PASS'
                : 'WARN';
        } else {
            $status = $passed
                ? 'PASS'
                : 'FAIL';
        }

        $checks[] = [
            'status' => $status,
            'check' => $name,
            'message' => $message,
        ];
    }
}
