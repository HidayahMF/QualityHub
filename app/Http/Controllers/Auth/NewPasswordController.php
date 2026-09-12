<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SessionSecurityService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view(
            'auth.reset-password',
            [
                'request' => $request,
            ]
        );
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(
        Request $request,
        SessionSecurityService $sessionSecurity
    ): RedirectResponse {
        $request->merge([
            'email' => Str::lower(
                trim((string) $request->input('email'))
            ),
        ]);

        $passwordMax = max(
            64,
            (int) config(
                'smartfind_security.auth.password_max_length',
                128
            )
        );

        $validated = $request->validate([
            'token' => [
                'required',
                'string',
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                'max:'.$passwordMax,
                'confirmed',
                Rules\Password::defaults(),
            ],
        ]);

        $status = Password::reset(
            [
                'email' => $validated['email'],
                'password' => $validated['password'],
                'password_confirmation' =>
                    (string) $request->input('password_confirmation'),
                'token' => $validated['token'],
            ],
            function (User $user) use (
                $validated,
                $sessionSecurity
            ) {
                $user->forceFill([
                    'password' => Hash::make(
                        $validated['password']
                    ),
                    'remember_token' => Str::random(60),
                ])->save();

                /*
                | Password reset is a security boundary: revoke every existing
                | database session belonging to this account.
                */
                $sessionSecurity
                    ->revokeDatabaseSessionsForUser(
                        $user
                    );

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()
                ->route('login')
                ->with(
                    'status',
                    __($status)
                )
            : back()
                ->withInput([
                    'email' => $validated['email'],
                ])
                ->withErrors([
                    'email' => __($status),
                ]);
    }
}
