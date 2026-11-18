<?php

namespace App\Http\Middleware;

use App\Services\SessionSecurityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionFreshness
{
    public function __construct(
        private readonly SessionSecurityService $sessionSecurity
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $now = time();

        /*
        | Persistent login is disabled by default for this internal system.
        | If an old remember-cookie from before SH-4 authenticates a browser,
        | rotate the remember token and require a normal password login again.
        */
        $rememberAllowed =
            config(
                'smartfind_security.auth.remember_login_enabled',
                false
            ) === true;

        if (
            ! $rememberAllowed
            && Auth::guard('web')->viaRemember()
        ) {
            $user->forceFill([
                'remember_token' => Str::random(60),
            ])->saveQuietly();

            $this->sessionSecurity
                ->revokeDatabaseSessionsForUser($user);

            return $this->revoke(
                $request,
                'Login persisten tidak diizinkan. Silakan masuk kembali menggunakan kata sandi.'
            );
        }

        $boundUserId = $request
            ->session()
            ->get(
                SessionSecurityService::USER_ID_KEY
            );

        $authenticatedAt = $request
            ->session()
            ->get(
                SessionSecurityService::AUTHENTICATED_AT_KEY
            );

        $lastActivity = $request
            ->session()
            ->get(
                SessionSecurityService::LAST_ACTIVITY_KEY
            );

        $storedPasswordFingerprint = $request
            ->session()
            ->get(
                SessionSecurityService::PASSWORD_FINGERPRINT_KEY
            );

        /*
        | Existing sessions from before SH-4 do not have the new markers yet.
        | Initialize them instead of force-logging every user out during deploy.
        */
        if (
            ! is_numeric($boundUserId)
            || ! is_numeric($authenticatedAt)
            || ! is_numeric($lastActivity)
            || ! is_string($storedPasswordFingerprint)
            || $storedPasswordFingerprint === ''
        ) {
            $this->sessionSecurity->initialize(
                $request,
                $user
            );

            $boundUserId =
                (int) $user->getAuthIdentifier();
            $authenticatedAt = $now;
            $lastActivity = $now;
            $storedPasswordFingerprint =
                $this->sessionSecurity
                    ->passwordFingerprint($user);
        }

        /*
        | Bind a session to the authenticated user ID. This should never differ
        | during a normal Laravel web session.
        */
        if (
            (int) $boundUserId
            !== (int) $user->getAuthIdentifier()
        ) {
            return $this->revoke(
                $request,
                'Sesi tidak valid. Silakan login kembali.'
            );
        }

        $absoluteTimeoutMinutes = max(
            0,
            (int) config(
                'smartfind_security.session.absolute_timeout_minutes',
                720
            )
        );

        if (
            $absoluteTimeoutMinutes > 0
            && (
                $now - (int) $authenticatedAt
            ) > (
                $absoluteTimeoutMinutes * 60
            )
        ) {
            return $this->revoke(
                $request,
                'Sesi Anda telah mencapai batas waktu maksimum. Silakan login kembali.'
            );
        }

        $idleTimeoutMinutes = max(
            0,
            (int) config(
                'smartfind_security.session.idle_timeout_minutes',
                60
            )
        );

        if (
            $idleTimeoutMinutes > 0
            && (
                $now - (int) $lastActivity
            ) > (
                $idleTimeoutMinutes * 60
            )
        ) {
            return $this->revoke(
                $request,
                'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.'
            );
        }

        $fingerprintEnabled =
            config(
                'smartfind_security.session.password_fingerprint_enabled',
                true
            ) === true;

        if ($fingerprintEnabled) {
            $currentFingerprint =
                $this->sessionSecurity
                    ->passwordFingerprint($user);

            if (! hash_equals(
                $currentFingerprint,
                (string) $storedPasswordFingerprint
            )) {
                return $this->revoke(
                    $request,
                    'Kredensial akun telah berubah. Silakan login kembali.'
                );
            }
        }

        $request
            ->session()
            ->put(
                SessionSecurityService::LAST_ACTIVITY_KEY,
                $now
            );

        return $next($request);
    }

    private function revoke(
        Request $request,
        string $message
    ): Response {
        Auth::guard('web')->logout();

        $request
            ->session()
            ->invalidate();

        $request
            ->session()
            ->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(
                [
                    'message' => $message,
                ],
                401
            );
        }

        return redirect()
            ->route('login')
            ->withErrors([
                'email' => $message,
            ]);
    }
}
