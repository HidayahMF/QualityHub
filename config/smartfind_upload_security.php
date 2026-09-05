<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Extensions
    |--------------------------------------------------------------------------
    */

    'supported_extensions' => [
        'pdf',
        'xls',
        'xlsx',
        'doc',
        'docx',
        'csv',
        'txt',
    ],


    /*
    |--------------------------------------------------------------------------
    | File Size
    |--------------------------------------------------------------------------
    |
    | Harus konsisten dengan validation max:20480 pada controller.
    |
    */

    'max_file_size_bytes' =>
        20 * 1024 * 1024,


    /*
    |--------------------------------------------------------------------------
    | Text Sample
    |--------------------------------------------------------------------------
    */

    'text_sample_bytes' =>
        65536,


    /*
    |--------------------------------------------------------------------------
    | MIME Whitelist
    |--------------------------------------------------------------------------
    |
    | MIME berasal dari server-side fileinfo, bukan Content-Type browser.
    |
    | Signature / container structure tetap diverifikasi setelah MIME.
    |
    */

    'allowed_mimes' => [

        'pdf' => [
            'application/pdf',
        ],

        'xls' => [
            'application/vnd.ms-excel',
            'application/x-ole-storage',
            'application/cdfv2',
            'application/x-cdf',
        ],

        'xlsx' => [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'application/x-zip-compressed',
        ],

        'doc' => [
            'application/msword',
            'application/x-ole-storage',
            'application/cdfv2',
            'application/x-cdf',
        ],

        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/x-zip-compressed',
        ],

        'csv' => [
            'text/csv',
            'text/plain',
            'application/csv',
            'application/vnd.ms-excel',
        ],

        'txt' => [
            'text/plain',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Generic MIME Fallback
    |--------------------------------------------------------------------------
    |
    | Digunakan hanya untuk format Office yang kadang dibaca fileinfo sebagai
    | binary generic. File tetap harus lolos pemeriksaan magic/structure.
    |
    */

    'generic_mime_fallbacks' => [
        'application/octet-stream',
    ],
];
