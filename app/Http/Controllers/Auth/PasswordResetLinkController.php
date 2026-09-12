<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * Response dibuat generik untuk mengurangi account enumeration. User tidak
     * diberi tahu apakah email benar-benar terdaftar pada BMC QualityHub.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(
                trim((string) $request->input('email'))
            ),
        ]);

        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
            ],
        ]);

        Password::sendResetLink([
            'email' => $validated['email'],
        ]);

        return back()->with(
            'status',
            'Jika alamat email terdaftar, tautan pengaturan ulang kata sandi akan dikirim.'
        );
    }
}
