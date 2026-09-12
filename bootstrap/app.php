<?php

use App\Http\Middleware\EnforceSameOrigin;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
        | SecurityHeaders harus menjadi lapisan terluar web request.
        |
        | Tujuannya:
        | - membuat CSP nonce sebelum Blade apa pun dirender;
        | - memastikan response error awal (mis. 403 Same-Origin) tetap mendapat
        |   CSP / Permissions-Policy / browser security headers;
        | - EnforceSameOrigin tetap memblokir unsafe cross-origin request sebelum
        |   request masuk ke controller / auth handler.
        |
        | SecurityHeaders tetap idempotent karena route QualityHub saat ini juga
        | masih memasang middleware tersebut.
        */
        $middleware->web(
            prepend: [
                SecurityHeaders::class,
                EnforceSameOrigin::class,
            ]
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
