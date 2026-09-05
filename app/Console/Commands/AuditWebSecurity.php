<?php

namespace App\Console\Commands;

use App\Http\Middleware\EnsureSessionFreshness;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class AuditWebSecurity extends Command
{
    protected $signature =
        'smartfind:audit-web-security';

    protected $description =
        'Audit read-only session, CSRF, route middleware, dan production web security configuration.';


    public function handle(): int
    {
        $checks = [];

        $this->check(
            $checks,
            'APP_KEY tersedia',
            is_string(
                config('app.key')
            )
            && trim(
                (string) config('app.key')
            ) !== '',
            'ERROR'
        );

        $environment =
            (string) app()
                ->environment();

        $isProduction =
            $environment === 'production';

        $this->check(
            $checks,
            'APP_DEBUG aman',
            ! $isProduction
            || config('app.debug') === false,
            $isProduction
                ? 'ERROR'
                : 'INFO',
            $isProduction
                ? 'Production wajib APP_DEBUG=false.'
                : 'Local development boleh APP_DEBUG=true.'
        );


        /*
        |--------------------------------------------------------------------------
        | Laravel Web / CSRF
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | CSRF Protection Audit — Laravel 12
        |--------------------------------------------------------------------------
        |
        | routes/web.php otomatis berada pada middleware group "web".
        |
        | Pada Laravel 12, ValidateCsrfToken adalah bagian default dari
        | web middleware stack. Route::list -vv pada project SmartFind
        | juga menunjukkan state-changing route profile.update memakai "web".
        |
        | bootstrap/app.php SmartFind tidak melakukan custom middleware
        | removal / CSRF exclusion, sehingga audit yang paling stabil di sini
        | adalah memastikan route mutasi tetap berada di group "web".
        |
        */

        $csrfProbeRouteNames = [
            'profile.update',
            'documents.store',
            'documents.revisions.store',
            'users.approve',
        ];

        $webProtectedRoute =
            null;

        foreach (
            $csrfProbeRouteNames
            as $routeName
        ) {
            $route =
                Route::getRoutes()
                    ->getByName(
                        $routeName
                    );

            if ($route === null) {
                continue;
            }

            $routeMiddleware =
                $route
                    ->gatherMiddleware();

            if (
                in_array(
                    'web',
                    $routeMiddleware,
                    true
                )
            ) {
                $webProtectedRoute =
                    $routeName;

                break;
            }
        }

        $this->check(
            $checks,
            'Laravel web/CSRF protection aktif',
            $webProtectedRoute !== null,
            'ERROR',
            $webProtectedRoute !== null
                ? 'State-changing route berada di middleware web: '
                    . $webProtectedRoute
                : 'Tidak ditemukan state-changing route yang memakai middleware web.'
        );


        /*
        |--------------------------------------------------------------------------
        | Session
        |--------------------------------------------------------------------------
        */

        $sessionDriver =
            strtolower(
                (string) config(
                    'session.driver',
                    ''
                )
            );

        $this->check(
            $checks,
            'Session driver persisten',
            $sessionDriver !== ''
            && $sessionDriver !== 'array',
            'ERROR',
            'Driver saat ini: '
            . (
                $sessionDriver !== ''
                    ? $sessionDriver
                    : '[empty]'
            )
        );

        $this->check(
            $checks,
            'Session encryption aktif',
            config(
                'session.encrypt'
            ) === true,
            'WARNING',
            'Disarankan SESSION_ENCRYPT=true.'
        );

        $this->check(
            $checks,
            'Session cookie HttpOnly',
            config(
                'session.http_only'
            ) === true,
            'ERROR',
            'SESSION_HTTP_ONLY harus true.'
        );

        $sameSite =
            strtolower(
                (string) config(
                    'session.same_site',
                    ''
                )
            );

        $this->check(
            $checks,
            'Session SameSite aman',
            in_array(
                $sameSite,
                [
                    'lax',
                    'strict',
                ],
                true
            ),
            'ERROR',
            'Saat ini: '
            . (
                $sameSite !== ''
                    ? $sameSite
                    : '[null]'
            )
        );

        $secureCookie =
            config(
                'session.secure'
            ) === true;

        $this->check(
            $checks,
            'Secure session cookie',
            ! $isProduction
            || $secureCookie,
            $isProduction
                ? 'ERROR'
                : 'INFO',
            $isProduction
                ? 'Production HTTPS wajib SESSION_SECURE_COOKIE=true.'
                : 'Local HTTP boleh false; production HTTPS harus true.'
        );

        $idleTimeout =
            (int) config(
                'smartfind_security.session.idle_timeout_minutes',
                0
            );

        $this->check(
            $checks,
            'SmartFind idle session timeout aktif',
            $idleTimeout > 0,
            'ERROR',
            'Timeout: '
            . $idleTimeout
            . ' menit.'
        );


        /*
        |--------------------------------------------------------------------------
        | Route Middleware
        |--------------------------------------------------------------------------
        */

        $dashboardRoute =
            Route::getRoutes()
                ->getByName(
                    'dashboard'
                );

        $dashboardMiddleware =
            $dashboardRoute !== null
                ? $dashboardRoute
                    ->gatherMiddleware()
                : [];

        $this->check(
            $checks,
            'Dashboard memakai SecurityHeaders',
            in_array(
                SecurityHeaders::class,
                $dashboardMiddleware,
                true
            ),
            'ERROR'
        );

        $this->check(
            $checks,
            'Dashboard memakai EnsureSessionFreshness',
            in_array(
                EnsureSessionFreshness::class,
                $dashboardMiddleware,
                true
            ),
            'ERROR'
        );

        $loginRoute =
            Route::getRoutes()
                ->getByName(
                    'login'
                );

        $loginMiddleware =
            $loginRoute !== null
                ? $loginRoute
                    ->gatherMiddleware()
                : [];

        $this->check(
            $checks,
            'Login page memakai SecurityHeaders',
            in_array(
                SecurityHeaders::class,
                $loginMiddleware,
                true
            ),
            'ERROR'
        );


        /*
        |--------------------------------------------------------------------------
        | Production Headers
        |--------------------------------------------------------------------------
        */

        $this->check(
            $checks,
            'CSP configuration',
            ! $isProduction
            || config(
                'smartfind_security.csp.enabled'
            ) === true,
            $isProduction
                ? 'WARNING'
                : 'INFO',
            $isProduction
                ? 'Disarankan SMARTFIND_CSP_ENABLED=true setelah production smoke test.'
                : 'CSP sengaja boleh disabled saat Vite/HMR local.'
        );

        $this->check(
            $checks,
            'HSTS configuration',
            ! $isProduction
            || config(
                'smartfind_security.hsts.enabled'
            ) === true,
            $isProduction
                ? 'WARNING'
                : 'INFO',
            $isProduction
                ? 'Aktifkan hanya setelah site benar-benar HTTPS.'
                : 'HSTS tidak diperlukan pada local HTTP.'
        );


        /*
        |--------------------------------------------------------------------------
        | Output
        |--------------------------------------------------------------------------
        */

        $rows =
            array_map(
                fn (array $check) => [
                    $check[
                        'status'
                    ],

                    $check[
                        'check'
                    ],

                    $check[
                        'message'
                    ],
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

        $errors =
            collect(
                $checks
            )
                ->where(
                    'status',
                    'FAIL'
                )
                ->count();

        $warnings =
            collect(
                $checks
            )
                ->where(
                    'status',
                    'WARN'
                )
                ->count();

        $this->newLine();

        if ($errors === 0) {

            $this->info(
                'WEB SECURITY AUDIT: PASS'
            );

        } else {

            $this->error(
                'WEB SECURITY AUDIT: FAIL'
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

            $status =
                $passed
                    ? 'INFO'
                    : 'INFO';

        } elseif ($severity === 'WARNING') {

            $status =
                $passed
                    ? 'PASS'
                    : 'WARN';

        } else {

            $status =
                $passed
                    ? 'PASS'
                    : 'FAIL';
        }

        $checks[] = [
            'status' =>
                $status,

            'check' =>
                $name,

            'message' =>
                $message,
        ];
    }
}
