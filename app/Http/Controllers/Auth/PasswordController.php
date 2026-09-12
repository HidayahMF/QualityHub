<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SessionSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(
        Request $request,
        SessionSecurityService $sessionSecurity
    ): RedirectResponse {
        $passwordMax = max(
            64,
            (int) config(
                'smartfind_security.auth.password_max_length',
                128
            )
        );

        $validated = $request->validateWithBag(
            'updatePassword',
            [
                'current_password' => [
                    'required',
                    'current_password',
                ],
                'password' => [
                    'required',
                    'string',
                    'max:'.$passwordMax,
                    Password::defaults(),
                    'confirmed',
                ],
            ]
        );

        $user = $request->user();

        /*
        | Rotate remember token so remembered sessions issued before SH-4 or
        | from another device can no longer silently re-authenticate.
        */
        $user->forceFill([
            'password' => Hash::make(
                $validated['password']
            ),
            'remember_token' => Str::random(60),
        ])->save();

        $sessionSecurity->rotateAfterPasswordChange(
            $request,
            $user
        );

        return back()->with(
            'status',
            'password-updated'
        );
    }
}
