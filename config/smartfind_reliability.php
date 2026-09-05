<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Private Storage Capacity
    |--------------------------------------------------------------------------
    |
    | Nilai ini hanya untuk health warning.
    | Tidak mengubah mekanisme upload maupun private storage.
    |
    */

    'private_storage_warning_free_bytes' =>
        (int) env(
            'SMARTFIND_STORAGE_WARNING_FREE_BYTES',
            2147483648
        ),

    'private_storage_critical_free_bytes' =>
        (int) env(
            'SMARTFIND_STORAGE_CRITICAL_FREE_BYTES',
            536870912
        ),


    /*
    |--------------------------------------------------------------------------
    | Analytics Health
    |--------------------------------------------------------------------------
    */

    'analytics_pending_stale_minutes' =>
        (int) env(
            'SMARTFIND_ANALYTICS_PENDING_STALE_MINUTES',
            15
        ),

    'analytics_processing_stale_minutes' =>
        (int) env(
            'SMARTFIND_ANALYTICS_PROCESSING_STALE_MINUTES',
            30
        ),


    /*
    |--------------------------------------------------------------------------
    | Queue Health
    |--------------------------------------------------------------------------
    */

    'queue_pending_warning_count' =>
        (int) env(
            'SMARTFIND_QUEUE_PENDING_WARNING_COUNT',
            100
        ),


    /*
    |--------------------------------------------------------------------------
    | Backup Health
    |--------------------------------------------------------------------------
    */

    'backup_warning_age_hours' =>
        (int) env(
            'SMARTFIND_BACKUP_WARNING_AGE_HOURS',
            48
        ),

    'backup_required_in_production' =>
        filter_var(
            env(
                'SMARTFIND_BACKUP_REQUIRED_IN_PRODUCTION',
                true
            ),
            FILTER_VALIDATE_BOOL
        ),


    /*
    |--------------------------------------------------------------------------
    | Controlled Analytics Retry
    |--------------------------------------------------------------------------
    */

    'analytics_retry_default_limit' =>
        (int) env(
            'SMARTFIND_ANALYTICS_RETRY_LIMIT',
            25
        ),

    'analytics_retry_max_limit' =>
        (int) env(
            'SMARTFIND_ANALYTICS_RETRY_MAX_LIMIT',
            100
        ),

];
