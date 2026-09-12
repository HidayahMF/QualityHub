<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize credential input before validation and authentication.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(
                trim((string) $this->input('email'))
            ),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $passwordMax = max(
            64,
            (int) config(
                'smartfind_security.auth.password_max_length',
                128
            )
        );

        return [
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
            ],
            'remember' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * Dua limiter digunakan:
     * - email + IP: melindungi satu akun dari brute force;
     * - IP global: mengurangi credential spraying ke banyak akun.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $rememberAllowed =
            config(
                'smartfind_security.auth.remember_login_enabled',
                false
            ) === true;

        $remember =
            $rememberAllowed
            && $this->boolean('remember');

        if (! Auth::attempt(
            [
                'email' => (string) $this->input('email'),
                'password' => (string) $this->input('password'),
            ],
            $remember
        )) {
            $this->hitRateLimiters();

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $user = Auth::user();

        if (
            $user === null
            || strtolower((string) $user->account_status) !== 'active'
        ) {
            $status =
                $user?->account_status
                ?? 'inactive';

            Auth::guard('web')->logout();

            if ($status === 'pending') {
                throw ValidationException::withMessages([
                    'email' =>
                        'Akun Anda masih menunggu persetujuan administrator. Silakan hubungi administrator untuk informasi lebih lanjut.',
                ]);
            }

            throw ValidationException::withMessages([
                'email' =>
                    'Akun Anda saat ini tidak aktif. Silakan hubungi administrator.',
            ]);
        }

        $this->clearRateLimiters();
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $accountAttempts = max(
            1,
            (int) config(
                'smartfind_security.auth.login_max_attempts',
                5
            )
        );

        $ipAttempts = max(
            1,
            (int) config(
                'smartfind_security.auth.login_ip_max_attempts',
                20
            )
        );

        $accountLimited = RateLimiter::tooManyAttempts(
            $this->throttleKey(),
            $accountAttempts
        );

        $ipLimited = RateLimiter::tooManyAttempts(
            $this->ipThrottleKey(),
            $ipAttempts
        );

        if (! $accountLimited && ! $ipLimited) {
            return;
        }

        event(new Lockout($this));

        $seconds = max(
            RateLimiter::availableIn($this->throttleKey()),
            RateLimiter::availableIn($this->ipThrottleKey())
        );

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the account + IP rate limiting key.
     */
    public function throttleKey(): string
    {
        $identity = Str::lower(
            trim((string) $this->input('email'))
        );

        return 'qualityhub-login-account:'
            . hash(
                'sha256',
                $identity.'|'.$this->ip()
            );
    }

    /**
     * Get the IP-wide credential spraying limiter key.
     */
    public function ipThrottleKey(): string
    {
        return 'qualityhub-login-ip:'
            . hash(
                'sha256',
                (string) $this->ip()
            );
    }

    private function hitRateLimiters(): void
    {
        RateLimiter::hit(
            $this->throttleKey(),
            max(
                60,
                (int) config(
                    'smartfind_security.auth.login_decay_seconds',
                    300
                )
            )
        );

        RateLimiter::hit(
            $this->ipThrottleKey(),
            max(
                60,
                (int) config(
                    'smartfind_security.auth.login_ip_decay_seconds',
                    300
                )
            )
        );
    }

    private function clearRateLimiters(): void
    {
        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->ipThrottleKey());
    }
}
