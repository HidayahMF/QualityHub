<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Scan / Capture QC Image Security
    |--------------------------------------------------------------------------
    |
    | Defaults dibuat cukup longgar untuk foto kamera modern, tetapi tetap
    | memberi batas agar file/metadata gambar ekstrem tidak membebani server.
    |
    */

    'allowed_extensions' => [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'gif',
        'bmp',
        'tif',
        'tiff',
    ],

    'max_file_size_bytes' => (int) env(
        'SMARTFIND_SCAN_MAX_FILE_SIZE_BYTES',
        20 * 1024 * 1024
    ),

    /* Maksimum jumlah foto dalam satu dokumen Scan / Capture. */
    'max_files' => (int) env(
        'SMARTFIND_SCAN_MAX_FILES',
        10
    ),

    /* Total seluruh foto sumber sebelum digabung menjadi PDF. */
    'max_total_source_bytes' => (int) env(
        'SMARTFIND_SCAN_MAX_TOTAL_SOURCE_BYTES',
        60 * 1024 * 1024
    ),

    /* Batas akhir PDF gabungan yang disimpan ke private storage. */
    'max_combined_pdf_size_bytes' => (int) env(
        'SMARTFIND_SCAN_MAX_COMBINED_PDF_SIZE_BYTES',
        50 * 1024 * 1024
    ),

    'max_width' => (int) env(
        'SMARTFIND_SCAN_MAX_WIDTH',
        12000
    ),

    'max_height' => (int) env(
        'SMARTFIND_SCAN_MAX_HEIGHT',
        12000
    ),

    'max_pixels' => (int) env(
        'SMARTFIND_SCAN_MAX_PIXELS',
        80000000
    ),
];
