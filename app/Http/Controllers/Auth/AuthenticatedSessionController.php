<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\SessionSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(
        LoginRequest $request,
        SessionSecurityService $sessionSecurity
    ): RedirectResponse {
        $request->authenticate();

        /*
        | Session fixation protection: rotate ID immediately after login.
        */
        $request->session()->regenerate();

        $user = $request->user();

        $sessionSecurity->initialize(
            $request,
            $user
        );

        /*
        | Database sessions: keep at most N active sessions per user.
        */
        $sessionSecurity->pruneConcurrentSessions(
            $user,
            $request->session()->getId()
        );

        $user->update([
            'last_login_at' => now(),
        ]);

        return redirect()->intended(
            route(
                'dashboard',
                absolute: false
            )
        );
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
