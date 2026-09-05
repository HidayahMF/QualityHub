<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Production Infrastructure Expectations
    |--------------------------------------------------------------------------
    |
    | Technical identifier tetap menggunakan SMARTFIND_* agar kompatibel
    | dengan konfigurasi project yang sudah ada walaupun nama aplikasi tampil
    | sekarang adalah BMC QualityHub.
    |
    */

    'require_https' =>
        filter_var(
            env(
                'SMARTFIND_PRODUCTION_REQUIRE_HTTPS',
                true
            ),
            FILTER_VALIDATE_BOOL
        ),

    'require_database_session' =>
        filter_var(
            env(
                'SMARTFIND_PRODUCTION_REQUIRE_DATABASE_SESSION',
                true
            ),
            FILTER_VALIDATE_BOOL
        ),

    'require_encrypted_session' =>
        filter_var(
            env(
                'SMARTFIND_PRODUCTION_REQUIRE_ENCRYPTED_SESSION',
                true
            ),
            FILTER_VALIDATE_BOOL
        ),

    'require_database_queue' =>
        filter_var(
            env(
                'SMARTFIND_PRODUCTION_REQUIRE_DATABASE_QUEUE',
                true
            ),
            FILTER_VALIDATE_BOOL
        ),

    'require_vite_build' =>
        filter_var(
            env(
                'SMARTFIND_PRODUCTION_REQUIRE_VITE_BUILD',
                true
            ),
            FILTER_VALIDATE_BOOL
        ),


    /*
    |--------------------------------------------------------------------------
    | NAS / Private Document Storage
    |--------------------------------------------------------------------------
    |
    | Default target sesuai deployment plan saat ini:
    | mapped NAS drive H:
    |
    | UNC root tetap diperbolehkan untuk deployment Windows Service yang
    | tidak dapat melihat mapped drive milik user interaktif.
    |
    */

    'require_external_private_storage' =>
        filter_var(
            env(
                'SMARTFIND_PRODUCTION_REQUIRE_EXTERNAL_STORAGE',
                true
            ),
            FILTER_VALIDATE_BOOL
        ),

    'expected_nas_drive' =>
        env(
            'SMARTFIND_PRODUCTION_EXPECTED_NAS_DRIVE',
            'H:'
        ),

    'allow_unc_storage_root' =>
        filter_var(
            env(
                'SMARTFIND_PRODUCTION_ALLOW_UNC_STORAGE',
                true
            ),
            FILTER_VALIDATE_BOOL
        ),

    'minimum_private_storage_free_bytes' =>
        (int) env(
            'SMARTFIND_PRODUCTION_MIN_STORAGE_FREE_BYTES',
            5368709120
        ),


    /*
    |--------------------------------------------------------------------------
    | Required Runtime Tables
    |--------------------------------------------------------------------------
    */

    'required_tables' => [
        'users',
        'departments',
        'categories',
        'documents',
        'document_versions',
        'favorites',
        'activity_logs',
        'document_analytics',
        'document_analytic_sheets',
        'sessions',
        'jobs',
        'failed_jobs',
    ],


    /*
    |--------------------------------------------------------------------------
    | Existing Security Middleware
    |--------------------------------------------------------------------------
    |
    | Hanya mengecek class yang sudah dibangun di Tahap 17A/17D.
    | Tidak mengganti middleware atau security configuration.
    |
    */

    'required_middleware_classes' => [
        \App\Http\Middleware\EnsureActiveAccount::class,
        \App\Http\Middleware\EnsureSessionFreshness::class,
        \App\Http\Middleware\SecurityHeaders::class,
    ],

];
