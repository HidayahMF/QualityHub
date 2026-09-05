<?php

namespace App\Console\Commands;

use App\Services\SmartFindBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class BackupSmartFind extends Command
{
    protected $signature =
        'smartfind:backup';

    protected $description =
        'Membuat full backup database + private documents BMC SmartFind dengan SHA-256 manifest.';


    public function handle(
        SmartFindBackupService $backupService
    ): int {

        if (
            app()->environment(
                'production'
            )
            && (bool) config(
                'smartfind_backup.require_maintenance_in_production',
                true
            )
            && ! app()
                ->isDownForMaintenance()
        ) {
            $this->error(
                'Backup production memerlukan maintenance mode.'
            );

            $this->line(
                'Jalankan: php artisan down --retry=60'
            );

            return self::FAILURE;
        }

        $this->info(
            'Menjalankan pre-backup data integrity audit...'
        );

        try {

            $result =
                $backupService
                    ->createFullBackup();

        } catch (Throwable $exception) {

            $this->error(
                'SMARTFIND BACKUP: FAIL'
            );

            $this->line(
                $exception->getMessage()
            );

            Log::error(
                'BMC SmartFind backup failed.',
                [
                    'error' =>
                        $exception->getMessage(),
                ]
            );

            return self::FAILURE;
        }

        $this->newLine();

        $this->table(
            [
                'Backup',
                'Database Bytes',
                'Document Files',
                'Document Bytes',
                'Integrity Warning',
            ],
            [[
                $result[
                    'backup_name'
                ],
                number_format(
                    $result[
                        'database_bytes'
                    ]
                ),
                $result[
                    'document_files'
                ],
                number_format(
                    $result[
                        'document_bytes'
                    ]
                ),
                $result[
                    'integrity_warnings'
                ],
            ]]
        );

        $this->newLine();

        $this->info(
            'SMARTFIND BACKUP: PASS'
        );

        $this->line(
            'Backup path: '
            . $result['path']
        );

        $this->line(
            'Verifikasi: php artisan smartfind:backup-verify '
            . $result[
                'backup_name'
            ]
        );

        Log::info(
            'BMC SmartFind backup completed.',
            [
                'backup_name' =>
                    $result[
                        'backup_name'
                    ],

                'document_files' =>
                    $result[
                        'document_files'
                    ],
            ]
        );

        return self::SUCCESS;
    }
}
