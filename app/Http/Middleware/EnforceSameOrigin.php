<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EnforceSameOrigin
{
    /**
     * Method yang tidak mengubah state tidak perlu diblokir oleh same-origin guard.
     */
    private const SAFE_METHODS = [
        'GET',
        'HEAD',
        'OPTIONS',
    ];

    public function handle(
        Request $request,
        Closure $next
    ): Response {

        /*
        |--------------------------------------------------------------------------
        | Authentication lifecycle compatibility
        |--------------------------------------------------------------------------
        |
        | Logout Laravel sudah dilindungi oleh middleware web + auth + CSRF.
        | Same-origin guard tambahan tidak perlu memproses endpoint ini karena
        | sebagian browser/privacy configuration dapat mengirim Origin/Referer
        | yang berbeda atau tidak lengkap saat session sedang diakhiri.
        |
        | Melewati guard tambahan di sini TIDAK mengubah route menjadi GET dan
        | TIDAK menonaktifkan CSRF. POST /logout tetap harus membawa CSRF token.
        |
        */
        if ($request->routeIs('logout')) {
            return $next($request);
        }

        if (
            ! (bool) config(
                'smartfind_security.same_origin.enabled',
                true
            )
            || in_array(
                strtoupper($request->method()),
                self::SAFE_METHODS,
                true
            )
        ) {
            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | Fetch Metadata — additional browser signal
        |--------------------------------------------------------------------------
        |
        | Jika browser secara eksplisit mengatakan request berasal dari cross-site,
        | tolak lebih awal. Browser lama yang tidak mengirim header ini tetap dapat
        | berjalan karena Origin / Referer menjadi pemeriksaan utama di bawah.
        |
        */

        $fetchSite = strtolower(
            trim(
                (string) $request->headers->get(
                    'Sec-Fetch-Site',
                    ''
                )
            )
        );

        if ($fetchSite === 'cross-site') {
            throw new HttpException(
                403,
                'Cross-site request ditolak oleh BMC QualityHub.'
            );
        }

        $requestOrigin =
            $this->normalizeOrigin(
                $request->getSchemeAndHttpHost()
            );

        if ($requestOrigin === null) {
            throw new HttpException(
                403,
                'Origin request tidak valid.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Origin Header
        |--------------------------------------------------------------------------
        */

        $originHeader = trim(
            (string) $request->headers->get(
                'Origin',
                ''
            )
        );

        if ($originHeader !== '') {
            $origin =
                $this->normalizeOrigin(
                    $originHeader
                );

            if (
                $origin === null
                || ! hash_equals(
                    $requestOrigin,
                    $origin
                )
            ) {
                throw new HttpException(
                    403,
                    'Cross-origin request ditolak oleh BMC QualityHub.'
                );
            }

            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | Referer Fallback
        |--------------------------------------------------------------------------
        |
        | Sebagian client/browser mungkin tidak mengirim Origin. Jika Referer ada,
        | origin-nya tetap harus sama dengan host yang sedang diakses.
        |
        */

        $refererHeader = trim(
            (string) $request->headers->get(
                'Referer',
                ''
            )
        );

        if ($refererHeader !== '') {

            /*
            | Laravel feature tests dan beberapa non-browser client dapat
            | menggunakan Referer relatif seperti "/profile". Referer relatif
            | tidak membawa informasi origin, jadi jangan dianggap cross-origin.
            | Biarkan CSRF menjadi kontrol utama untuk request seperti ini.
            */
            if (! str_starts_with($refererHeader, '/')) {
                $refererOrigin =
                    $this->normalizeOrigin(
                        $refererHeader
                    );

                if (
                    $refererOrigin === null
                    || ! hash_equals(
                        $requestOrigin,
                        $refererOrigin
                    )
                ) {
                    throw new HttpException(
                        403,
                        'Cross-origin referer ditolak oleh BMC QualityHub.'
                    );
                }
            }
        }

        /*
        | Jika Origin dan Referer sama-sama tidak tersedia, request tidak ditolak
        | di sini. Laravel CSRF tetap menjadi kontrol utama untuk request web yang
        | mengubah state, sehingga CLI/test/client kompatibel tetap dapat bekerja.
        */

        return $next($request);
    }


    private function normalizeOrigin(
        string $url
    ): ?string {

        $parts = parse_url(
            trim($url)
        );

        if (
            ! is_array($parts)
            || ! isset(
                $parts['scheme'],
                $parts['host']
            )
        ) {
            return null;
        }

        $scheme = strtolower(
            (string) $parts['scheme']
        );

        if (
            ! in_array(
                $scheme,
                ['http', 'https'],
                true
            )
        ) {
            return null;
        }

        $host = strtolower(
            (string) $parts['host']
        );

        if ($host === '') {
            return null;
        }

        $port = isset($parts['port'])
            ? (int) $parts['port']
            : null;

        $isDefaultPort =
            ($scheme === 'http' && $port === 80)
            || ($scheme === 'https' && $port === 443);

        return $scheme
            . '://'
            . $host
            . (
                $port !== null
                && ! $isDefaultPort
                    ? ':' . $port
                    : ''
            );
    }
}
