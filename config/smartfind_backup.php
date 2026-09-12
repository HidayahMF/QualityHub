<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup Root
    |--------------------------------------------------------------------------
    |
    | Untuk production, arahkan ke drive / lokasi yang BERBEDA dari
    | private document storage agar backup tidak ikut hilang apabila
    | storage utama rusak.
    |
    */

    'root' =>
        env(
            'SMARTFIND_BACKUP_PATH',
            storage_path(
                'app/smartfind-backups'
            )
        ),


    /*
    |--------------------------------------------------------------------------
    | Private Document Disk
    |--------------------------------------------------------------------------
    */

    'document_disk' =>
        'bmc_documents',


    /*
    |--------------------------------------------------------------------------
    | Database Dump
    |--------------------------------------------------------------------------
    */

    'mysqldump_binary' =>
        env(
            'SMARTFIND_MYSQLDUMP_PATH'
        ),

    /*
    |--------------------------------------------------------------------------
    | Dedicated Backup Database Credentials
    |--------------------------------------------------------------------------
    |
    | Jangan pakai credential runtime Laravel untuk mysqldump.
    | Akun backup hanya memiliki privilege yang diperlukan untuk logical backup.
    |
    */

    'database_username' =>
        env(
            'SMARTFIND_BACKUP_DB_USERNAME'
        ),

    'database_password' =>
        env(
            'SMARTFIND_BACKUP_DB_PASSWORD'
        ),

    'database_timeout_seconds' =>
        (int) env(
            'SMARTFIND_BACKUP_DB_TIMEOUT',
            300
        ),


    /*
    |--------------------------------------------------------------------------
    | Consistency Protection
    |--------------------------------------------------------------------------
    |
    | Production backup sebaiknya dibuat ketika aplikasi berada dalam
    | maintenance mode agar database dan private documents merupakan
    | snapshot yang konsisten.
    |
    */

    'require_maintenance_in_production' =>
        filter_var(
            env(
                'SMARTFIND_BACKUP_REQUIRE_MAINTENANCE',
                true
            ),
            FILTER_VALIDATE_BOOL
        ),


    /*
    |--------------------------------------------------------------------------
    | Free Space Safety Margin
    |--------------------------------------------------------------------------
    */

    'minimum_free_space_margin_bytes' =>
        (int) env(
            'SMARTFIND_BACKUP_FREE_SPACE_MARGIN',
            52428800
        ),

];
