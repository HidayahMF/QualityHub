<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authenticated Session Hardening
    |--------------------------------------------------------------------------
    */

    'session' => [

        /*
        | Tambahan di atas Laravel SESSION_LIFETIME.
        | 0 = disable custom idle timeout.
        */

        'idle_timeout_minutes' =>
            (int) env(
                'SMARTFIND_IDLE_TIMEOUT_MINUTES',
                60
            ),
    ],


    /*
    |--------------------------------------------------------------------------
    | Same-Origin Request Hardening
    |--------------------------------------------------------------------------
    |
    | Defense in depth di atas CSRF. Untuk request yang mengubah state,
    | browser yang mengirim Origin / Referer harus berasal dari origin yang
    | sama dengan BMC QualityHub yang sedang diakses.
    |
    | Tidak membutuhkan domain/IP production yang sudah final karena middleware
    | membandingkan origin secara dinamis terhadap request host saat ini.
    |
    */

    'same_origin' => [

        'enabled' =>
            filter_var(
                env(
                    'SMARTFIND_SAME_ORIGIN_ENABLED',
                    true
                ),
                FILTER_VALIDATE_BOOL
            ),
    ],


    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    */

    'headers' => [

        'permissions_policy' =>
            env(
                'SMARTFIND_PERMISSIONS_POLICY',
                'camera=(self), microphone=(), geolocation=(), payment=(), usb=()'
            ),
    ],


    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | Default false agar development Vite/HMR tidak terganggu.
    |
    | Current SmartFind masih memiliki inline Blade/Alpine script/style.
    | Karena itu policy production sementara mempertahankan unsafe-inline
    | dan unsafe-eval untuk kompatibilitas.
    |
    | Tahap CSP strict nonce dapat dilakukan terpisah bila inline code
    | sudah direfactor.
    |
    */

    'csp' => [

        'enabled' =>
            filter_var(
                env(
                    'SMARTFIND_CSP_ENABLED',
                    false
                ),
                FILTER_VALIDATE_BOOL
            ),

        'policy' =>
            env(
                'SMARTFIND_CSP_POLICY',
                "default-src 'self'; "
                . "base-uri 'self'; "
                . "object-src 'none'; "
                . "frame-ancestors 'none'; "
                . "form-action 'self'; "
                . "img-src 'self' data:; "
                . "font-src 'self' data:; "
                . "style-src 'self' 'unsafe-inline'; "
                . "script-src 'self' 'unsafe-inline' 'unsafe-eval'; "
                . "connect-src 'self'"
            ),
    ],


    /*
    |--------------------------------------------------------------------------
    | HSTS
    |--------------------------------------------------------------------------
    |
    | Hanya dikirim jika request benar-benar HTTPS.
    |
    */

    'hsts' => [

        'enabled' =>
            filter_var(
                env(
                    'SMARTFIND_HSTS_ENABLED',
                    false
                ),
                FILTER_VALIDATE_BOOL
            ),

        'max_age' =>
            (int) env(
                'SMARTFIND_HSTS_MAX_AGE',
                31536000
            ),

        'include_subdomains' =>
            filter_var(
                env(
                    'SMARTFIND_HSTS_INCLUDE_SUBDOMAINS',
                    false
                ),
                FILTER_VALIDATE_BOOL
            ),

        'preload' =>
            filter_var(
                env(
                    'SMARTFIND_HSTS_PRELOAD',
                    false
                ),
                FILTER_VALIDATE_BOOL
            ),
    ],
];
