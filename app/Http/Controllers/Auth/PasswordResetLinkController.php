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

        /*
         * MAIL_MAILER=log/array tidak mengirim email.
         * Jangan membuat token reset yang justru tertulis di log server.
         * Failover yang jatuh ke log juga tidak boleh digunakan.
         */
        $mailer = (string) config('mail.default', 'log');
        $nonDeliveringMailers = ['log', 'array'];
        $fallbackMailers = $mailer === 'failover'
            ? (array) config('mail.mailers.failover.mailers', [])
            : [];

        if (
            in_array($mailer, $nonDeliveringMailers, true)
            || array_intersect($nonDeliveringMailers, $fallbackMailers) !== []
        ) {
            return back()->withErrors([
                'email' => 'Pemulihan kata sandi lewat email belum tersedia. Hubungi administrator IT.',
            ]);
        }

        try {
            Password::sendResetLink([
                'email' => $validated['email'],
            ]);
        } catch (\Throwable $exception) {
            /*
             * Jangan membedakan email terdaftar vs tidak terdaftar
             * lewat pesan kesalahan transport SMTP. Kesalahan teknis
             * dicatat agar dapat ditindaklanjuti oleh administrator.
             */
            report($exception);
        }

        return back()->with(
            'status',
            'Jika alamat email terdaftar, sistem akan mencoba mengirimkan tautan pengaturan ulang kata sandi. Jika tidak diterima, hubungi administrator IT.'
        );
    }
}
