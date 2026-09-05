<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {

        $response =
            $next(
                $request
            );

        /*
        |--------------------------------------------------------------------------
        | Baseline Security Headers
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
                'camera=(), microphone=(), geolocation=(), payment=(), usb=()'
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


        /*
        |--------------------------------------------------------------------------
        | Sensitive Authenticated HTML Pages
        |--------------------------------------------------------------------------
        |
        | Mencegah browser / intermediary menyimpan halaman internal SmartFind
        | yang berisi metadata dokumen atau data user.
        |
        */

        $contentType =
            strtolower(
                (string) $response
                    ->headers
                    ->get(
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
        | Content Security Policy
        |--------------------------------------------------------------------------
        |
        | Disabled by default during local development so Vite / HMR is not
        | broken. Enable deliberately in production after deployment test.
        |
        | Policy keeps compatibility with current Blade/Alpine inline code.
        |
        */

        if (
            (bool) config(
                'smartfind_security.csp.enabled',
                false
            )
        ) {
            $policy =
                trim(
                    (string) config(
                        'smartfind_security.csp.policy',
                        ''
                    )
                );

            if ($policy !== '') {
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
        |
        | Never emit HSTS on local HTTP development.
        |
        */

        if (
            $request->isSecure()
            && (bool) config(
                'smartfind_security.hsts.enabled',
                false
            )
        ) {
            $maxAge =
                max(
                    0,
                    (int) config(
                        'smartfind_security.hsts.max_age',
                        31536000
                    )
                );

            $hsts =
                'max-age='
                . $maxAge;

            if (
                (bool) config(
                    'smartfind_security.hsts.include_subdomains',
                    false
                )
            ) {
                $hsts .=
                    '; includeSubDomains';
            }

            if (
                (bool) config(
                    'smartfind_security.hsts.preload',
                    false
                )
            ) {
                $hsts .=
                    '; preload';
            }

            $response->headers->set(
                'Strict-Transport-Security',
                $hsts
            );
        }

        return $response;
    }
}
