<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Spreadsheet Analytics
    |--------------------------------------------------------------------------
    |
    | Konfigurasi parser Excel / CSV BMC SmartFind.
    |
    | Parser hanya MEMBACA file dari private storage.
    | File sumber tidak pernah diubah oleh proses analytics.
    |
    */

    'disk' => env(
        'SMARTFIND_ANALYTICS_DISK',
        'bmc_documents'
    ),

    'supported_extensions' => [
        'xlsx',
        'xls',
        'csv',
    ],

    /*
    |--------------------------------------------------------------------------
    | Preview
    |--------------------------------------------------------------------------
    |
    | Hanya sejumlah baris berikut yang disimpan sebagai preview JSON.
    | Seluruh baris tetap dibaca untuk perhitungan statistik.
    |
    */

    'preview_rows' => (int) env(
        'SMARTFIND_ANALYTICS_PREVIEW_ROWS',
        100
    ),

    /*
    |--------------------------------------------------------------------------
    | Header Detection
    |--------------------------------------------------------------------------
    */

    'header_scan_rows' => (int) env(
        'SMARTFIND_ANALYTICS_HEADER_SCAN_ROWS',
        25
    ),

    /*
    |--------------------------------------------------------------------------
    | Safety Limits
    |--------------------------------------------------------------------------
    |
    | Bila batas terlampaui, parser GAGAL dengan pesan yang jelas.
    | Parser tidak membuat statistik parsial yang dapat menyesatkan.
    |
    */

    'max_file_size_mb' => (int) env(
        'SMARTFIND_ANALYTICS_MAX_FILE_SIZE_MB',
        50
    ),

    'max_rows_per_sheet' => (int) env(
        'SMARTFIND_ANALYTICS_MAX_ROWS_PER_SHEET',
        100000
    ),

    'max_columns_per_sheet' => (int) env(
        'SMARTFIND_ANALYTICS_MAX_COLUMNS_PER_SHEET',
        200
    ),

    /*
    |--------------------------------------------------------------------------
    | Statistics
    |--------------------------------------------------------------------------
    */

    'top_values_limit' => (int) env(
        'SMARTFIND_ANALYTICS_TOP_VALUES_LIMIT',
        10
    ),

    /*
    | Untuk menjaga memory, distinct value tracking dihentikan setelah
    | melewati batas ini. Bila terjadi, statistics akan menandai
    | unique_count_truncated = true.
    */

    'distinct_tracking_limit' => (int) env(
        'SMARTFIND_ANALYTICS_DISTINCT_TRACKING_LIMIT',
        5000
    ),

];
