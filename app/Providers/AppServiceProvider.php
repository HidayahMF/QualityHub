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
        | One consistent password rule for creating accounts, resetting a
        | user's password and changing one's own password.
        | Minimum 8 characters, without mandatory character categories.
        | Company IT may configure a stricter minimum if needed.
        */
        Password::defaults(function () {
            $minimum = max(
                8,
                (int) config('smartfind_security.auth.password_min_length', 8)
            );

            return Password::min($minimum);
        });
    }
}
