<?php

$csv = static function (string $value): array {
    return array_values(
        array_filter(
            array_map(
                static fn (string $item): string => trim($item),
                explode(',', $value)
            ),
            static fn (string $item): bool => $item !== ''
        )
    );
};

return [

    /*
    |--------------------------------------------------------------------------
    | CORS — Closed by Default
    |--------------------------------------------------------------------------
    |
    | BMC QualityHub saat ini adalah aplikasi Blade/Laravel same-origin.
    | Karena belum ada kebutuhan frontend lintas domain, tidak ada path yang
    | membuka CORS secara default.
    |
    | Jika di masa depan ada API/frontend terpisah, isi SMARTFIND_CORS_PATHS dan
    | SMARTFIND_CORS_ALLOWED_ORIGINS dengan path/origin yang benar-benar diperlukan.
    | Jangan gunakan wildcard (*) pada production internal BMC.
    |
    */

    'paths' => $csv(
        (string) env(
            'SMARTFIND_CORS_PATHS',
            ''
        )
    ),

    'allowed_methods' => [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS',
    ],

    'allowed_origins' => $csv(
        (string) env(
            'SMARTFIND_CORS_ALLOWED_ORIGINS',
            ''
        )
    ),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Accept',
        'Content-Type',
        'Origin',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [],

    'max_age' => 600,

    'supports_credentials' => true,
];
