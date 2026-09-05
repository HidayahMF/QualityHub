<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionFreshness
{
    private const LAST_ACTIVITY_KEY =
        'smartfind_last_activity_at';


    public function handle(
        Request $request,
        Closure $next
    ): Response {

        $user =
            $request->user();

        if ($user === null) {
            return $next(
                $request
            );
        }

        $timeoutMinutes =
            max(
                0,
                (int) config(
                    'smartfind_security.session.idle_timeout_minutes',
                    60
                )
            );

        /*
        | 0 berarti custom idle-timeout dimatikan.
        | Laravel session lifetime tetap berlaku.
        */

        if ($timeoutMinutes === 0) {
            return $next(
                $request
            );
        }

        $now =
            time();

        $lastActivity =
            $request
                ->session()
                ->get(
                    self::LAST_ACTIVITY_KEY
                );

        if (
            is_numeric(
                $lastActivity
            )
            && (
                $now
                - (int) $lastActivity
            ) > (
                $timeoutMinutes
                * 60
            )
        ) {
            /*
            |--------------------------------------------------------------------------
            | Idle Session Revoke
            |--------------------------------------------------------------------------
            */

            Auth::guard('web')
                ->logout();

            $request
                ->session()
                ->invalidate();

            $request
                ->session()
                ->regenerateToken();

            if (
                $request->expectsJson()
            ) {
                return response()->json(
                    [
                        'message' =>
                            'Sesi Anda telah berakhir karena tidak ada aktivitas.',
                    ],
                    401
                );
            }

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' =>
                        'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.',
                ]);
        }

        $request
            ->session()
            ->put(
                self::LAST_ACTIVITY_KEY,
                $now
            );

        return $next(
            $request
        );
    }
}
