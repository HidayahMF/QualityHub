<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Hardening
    |--------------------------------------------------------------------------
    */

    'auth' => [

        /*
        | BMC QualityHub adalah aplikasi internal. Persistent remember-me login
        | dinonaktifkan secara default untuk mengurangi risiko sesi tertinggal
        | pada perangkat bersama / perangkat lama.
        */
        'remember_login_enabled' =>
            filter_var(
                env(
                    'SMARTFIND_REMEMBER_LOGIN_ENABLED',
                    false
                ),
                FILTER_VALIDATE_BOOL
            ),

        /*
        | Rate limit per kombinasi email + IP.
        */
        'login_max_attempts' =>
            max(
                1,
                (int) env(
                    'SMARTFIND_LOGIN_MAX_ATTEMPTS',
                    5
                )
            ),

        'login_decay_seconds' =>
            max(
                60,
                (int) env(
                    'SMARTFIND_LOGIN_DECAY_SECONDS',
                    300
                )
            ),

        /*
        | Rate limit tambahan per IP untuk mengurangi credential spraying.
        */
        'login_ip_max_attempts' =>
            max(
                1,
                (int) env(
                    'SMARTFIND_LOGIN_IP_MAX_ATTEMPTS',
                    20
                )
            ),

        'login_ip_decay_seconds' =>
            max(
                60,
                (int) env(
                    'SMARTFIND_LOGIN_IP_DECAY_SECONDS',
                    300
                )
            ),

        /*
        | Password baru / reset / ganti password minimal 12 karakter dan wajib
        | kombinasi huruf besar-kecil, angka, dan simbol melalui Password::defaults().
        */
        'password_min_length' =>
            max(
                12,
                (int) env(
                    'SMARTFIND_PASSWORD_MIN_LENGTH',
                    12
                )
            ),

        'password_max_length' =>
            max(
                64,
                min(
                    255,
                    (int) env(
                        'SMARTFIND_PASSWORD_MAX_LENGTH',
                        128
                    )
                )
            ),
    ],


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
            max(
                0,
                (int) env(
                    'SMARTFIND_IDLE_TIMEOUT_MINUTES',
                    60
                )
            ),

        /*
        | Batas absolut sesi walaupun user terus aktif.
        | Default 12 jam agar masih cukup untuk satu shift kerja panjang.
        | 0 = disable absolute timeout.
        */
        'absolute_timeout_minutes' =>
            max(
                0,
                (int) env(
                    'SMARTFIND_ABSOLUTE_SESSION_TIMEOUT_MINUTES',
                    720
                )
            ),

        /*
        | Batas sesi aktif per user ketika SESSION_DRIVER=database.
        | Default 3 agar laptop + HP + satu perangkat cadangan tetap memungkinkan.
        | 0 = tidak membatasi concurrency.
        */
        'max_concurrent_sessions' =>
            max(
                0,
                (int) env(
                    'SMARTFIND_MAX_CONCURRENT_SESSIONS',
                    3
                )
            ),

        /*
        | Session menyimpan fingerprint dari password hash. Jika password berubah
        | di tempat lain, session lama otomatis ditolak pada request berikutnya.
        */
        'password_fingerprint_enabled' =>
            filter_var(
                env(
                    'SMARTFIND_SESSION_PASSWORD_FINGERPRINT',
                    true
                ),
                FILTER_VALIDATE_BOOL
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
    | Pada local development nilai ini boleh di-override false karena topologi
    | final server / reverse proxy PT BMC belum diketahui. Production nanti
    | wajib diaktifkan kembali setelah hostname + HTTPS final dikonfirmasi.
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
    | Content Security Policy — Tightening Stage 3A
    |--------------------------------------------------------------------------
    |
    | SH-5C3B menyelesaikan penghapusan unsafe-inline dari seluruh CSP QualityHub.
    |
    | Inline <style> milik source code QualityHub wajib memakai nonce unik
    | per response yang sama dengan nonce script. External stylesheet same-origin
    | tetap diizinkan melalui style-src 'self'.
    |
    | Native style attributes sudah dimigrasikan ke class/data attribute dan
    | direct element.style.property yang tervalidasi. style-src-attr sekarang
    | ditutup penuh dengan 'none'.
    |
    | script-src tetap nonce-only tanpa unsafe-inline / unsafe-eval.
    | blob: tetap diizinkan untuk preview Scan/Capture menggunakan
    | URL.createObjectURL(). Kamera tetap diatur oleh Permissions-Policy.
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
                . "img-src 'self' data: blob:; "
                . "font-src 'self' data:; "
                . "style-src 'self'; "
                . "style-src-elem 'self' __SMARTFIND_CSP_NONCE__; "
                . "style-src-attr 'none'; "
                . "script-src 'self' __SMARTFIND_CSP_NONCE__; "
                . "connect-src 'self'; "
                . "media-src 'self' blob:; "
                . "worker-src 'self' blob:; "
                . "manifest-src 'self'"
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
