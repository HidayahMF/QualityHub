<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | BMC QualityHub Password Baseline
        |--------------------------------------------------------------------------
        |
        | Password baru, reset password, dan ganti password yang menggunakan
        | Password::defaults() wajib memenuhi baseline berikut.
        |
        | Pemeriksaan breach online sengaja tidak dipaksakan agar aplikasi tetap
        | dapat bekerja di jaringan internal BMC yang mungkin tidak memiliki akses
        | internet keluar.
        |
        */
        Password::defaults(function () {
            $minimum = max(
                12,
                (int) config(
                    'smartfind_security.auth.password_min_length',
                    12
                )
            );

            return Password::min($minimum)
                ->mixedCase()
                ->numbers()
                ->symbols();
        });
    }
}
