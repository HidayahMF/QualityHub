<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    /**
     * Memastikan session lama tidak tetap dapat mengakses SmartFind
     * setelah akun diubah menjadi pending, inactive, atau rejected.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {

        $user = $request->user();

        /*
        | Middleware ditempatkan setelah auth.
        */

        if ($user === null) {
            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        |
        | Field status akun BMC SmartFind adalah:
        |
        | account_status
        |
        | BUKAN:
        |
        | status
        |
        */

        if (
            strtolower(
                (string) $user->account_status
            ) === 'active'
        ) {
            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | Revoke Existing Session
        |--------------------------------------------------------------------------
        */

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
                    'message' =>
                        'Akun Anda tidak memiliki status aktif.',
                ],
                403
            );
        }

        return redirect()
            ->route('login')
            ->withErrors([
                'email' =>
                    'Akun Anda tidak aktif. Hubungi administrator BMC SmartFind.',
            ]);
    }
}
