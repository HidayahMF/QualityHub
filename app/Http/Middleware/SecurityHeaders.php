<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    private const CSP_NONCE_PLACEHOLDER = '__SMARTFIND_CSP_NONCE__';

    private const REQUEST_NONCE_ATTRIBUTE = 'smartfind_csp_nonce';

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        /*
        |--------------------------------------------------------------------------
        | Per-response CSP nonce — idempotent
        |--------------------------------------------------------------------------
        |
        | SecurityHeaders sekarang juga dipasang sebagai lapisan awal web middleware
        | supaya halaman error yang dibuat sebelum route middleware (contoh: 403 dari
        | EnforceSameOrigin) tetap mempunyai nonce + browser security headers.
        |
        | Route QualityHub saat ini juga masih memasang SecurityHeaders. Karena itu
        | middleware HARUS reuse nonce pada Request yang sama, bukan membuat nonce
        | kedua. Dengan begitu CSP header dan nonce Blade selalu tetap sinkron.
        |
        */

        $existingNonce = $request->attributes->get(
            self::REQUEST_NONCE_ATTRIBUTE
        );

        $cspNonce =
            is_string($existingNonce)
            && $existingNonce !== ''
                ? $existingNonce
                : base64_encode(
                    random_bytes(18)
                );

        $request->attributes->set(
            self::REQUEST_NONCE_ATTRIBUTE,
            $cspNonce
        );

        View::share(
            'cspNonce',
            $cspNonce
        );

        $response = $next($request);

        /*
        |--------------------------------------------------------------------------
        | Baseline Browser Security Headers
        |--------------------------------------------------------------------------
        */

        $response->headers->set(
            'X-Content-Type-Options',
            'nosniff'
        );

        $response->headers->set(
            'X-Frame-Options',
            'DENY'
        );

        $response->headers->set(
            'Referrer-Policy',
            'no-referrer'
        );

        $response->headers->set(
            'Permissions-Policy',
            (string) config(
                'smartfind_security.headers.permissions_policy',
                'camera=(self), microphone=(), geolocation=(), payment=(), usb=()'
            )
        );

        $response->headers->set(
            'Cross-Origin-Opener-Policy',
            'same-origin'
        );

        $response->headers->set(
            'Cross-Origin-Resource-Policy',
            'same-origin'
        );

        $response->headers->set(
            'X-Permitted-Cross-Domain-Policies',
            'none'
        );

        $response->headers->set(
            'X-DNS-Prefetch-Control',
            'off'
        );

        /*
        |--------------------------------------------------------------------------
        | Sensitive Authenticated HTML Pages
        |--------------------------------------------------------------------------
        */

        $contentType = strtolower(
            (string) $response->headers->get(
                'Content-Type',
                ''
            )
        );

        if (
            $request->user() !== null
            && str_contains(
                $contentType,
                'text/html'
            )
        ) {
            $response->headers->set(
                'Cache-Control',
                'private, no-store, no-cache, must-revalidate'
            );

            $response->headers->set(
                'Pragma',
                'no-cache'
            );

            $response->headers->set(
                'Expires',
                '0'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Content Security Policy — SH-5C3A early-response compatible
        |--------------------------------------------------------------------------
        |
        | Placeholder nonce dipakai oleh script-src dan style-src-elem.
        | Nonce yang sama dipakai walaupun middleware masuk lebih dari satu kali
        | pada Request yang sama.
        |
        */

        if (
            (bool) config(
                'smartfind_security.csp.enabled',
                false
            )
        ) {
            $policy = trim(
                (string) config(
                    'smartfind_security.csp.policy',
                    ''
                )
            );

            if ($policy !== '') {
                $policy = str_replace(
                    self::CSP_NONCE_PLACEHOLDER,
                    "'nonce-{$cspNonce}'",
                    $policy
                );

                $response->headers->set(
                    'Content-Security-Policy',
                    $policy
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | HSTS — HTTPS ONLY
        |--------------------------------------------------------------------------
        */

        if (
            $request->isSecure()
            && (bool) config(
                'smartfind_security.hsts.enabled',
                false
            )
        ) {
            $maxAge = max(
                0,
                (int) config(
                    'smartfind_security.hsts.max_age',
                    31536000
                )
            );

            $hsts = 'max-age=' . $maxAge;

            if (
                (bool) config(
                    'smartfind_security.hsts.include_subdomains',
                    false
                )
            ) {
                $hsts .= '; includeSubDomains';
            }

            if (
                (bool) config(
                    'smartfind_security.hsts.preload',
                    false
                )
            ) {
                $hsts .= '; preload';
            }

            $response->headers->set(
                'Strict-Transport-Security',
                $hsts
            );
        }

        return $response;
    }
}
