<?php

use App\Http\Middleware\EnforceSameOrigin;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

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
        // Dependency resolution and middleware errors happen before the controller's
        // local try/catch. Keep the scan form usable for those failures too.
        $exceptions->render(function (Throwable $exception, Request $request): ?Response {
            if (
                $exception instanceof ValidationException
                || $exception instanceof HttpResponseException
                || $exception instanceof AuthenticationException
            ) {
                return null;
            }

            if (
                $request->isMethod('POST')
                && (
                    $request->is('documents/scan')
                    || $request->routeIs('documents.scan.store')
                )
            ) {
                report($exception);

                return redirect()
                    ->route('documents.scan.create')
                    ->withInput()
                    ->withErrors([
                        'files' => 'Berkas scan tidak dapat diproses. Periksa konfigurasi penyimpanan dan coba lagi.',
                    ]);
            }

            if (
                $request->isMethod('POST')
                && (
                    $request->is('documents/search/ai')
                    || $request->routeIs('documents.search.ai')
                )
            ) {
                report($exception);

                $query = trim(strip_tags((string) $request->input('query', '')));
                $query = mb_substr($query, 0, 160);

                return response()->json([
                    'available' => false,
                    'mode' => 'keyword_fallback',
                    'fallback' => true,
                    'query' => $query,
                    'entities' => [],
                    'count' => 0,
                    'results' => [],
                    'model' => null,
                    'message' => 'AI Smart Search lokal sedang tidak tersedia. Pencarian kata kunci lokal tetap dapat digunakan.',
                    'fallback_url' => route(
                        'documents.index',
                        ['search' => $query],
                        false
                    ),
                ]);
            }

            return null;
        });
    })->create();
