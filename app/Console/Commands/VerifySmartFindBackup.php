<?php

namespace App\Console\Commands;

use App\Services\SmartFindBackupService;
use Illuminate\Console\Command;
use Throwable;

class VerifySmartFindBackup extends Command
{
    protected $signature =
        'smartfind:backup-verify
        {backup : Nama folder backup, contoh smartfind-20260901-210000-a1b2c3d4}';

    protected $description =
        'Verifikasi manifest, ukuran file, dan SHA-256 full backup BMC SmartFind.';


    public function handle(
        SmartFindBackupService $backupService
    ): int {

        try {

            $result =
                $backupService
                    ->verifyBackup(
                        (string) $this->argument(
                            'backup'
                        )
                    );

        } catch (Throwable $exception) {

            $this->error(
                'BACKUP VERIFICATION: FAIL'
            );

            $this->line(
                $exception->getMessage()
            );

            return self::FAILURE;
        }

        $this->table(
            [
                'Status',
                'Backup',
                'Verified Files',
                'Failures',
            ],
            [[
                $result['status'],
                $result[
                    'backup_name'
                ],
                $result[
                    'verified_files'
                ],
                count(
                    $result[
                        'failures'
                    ]
                ),
            ]]
        );

        if (
            ! empty(
                $result[
                    'failures'
                ]
            )
        ) {
            $this->newLine();

            foreach (
                $result[
                    'failures'
                ]
                as $failure
            ) {
                $this->error(
                    '- '
                    . $failure
                );
            }
        }

        $this->newLine();

        if (
            $result[
                'status'
            ] === 'PASS'
        ) {
            $this->info(
                'BACKUP VERIFICATION: PASS'
            );

            return self::SUCCESS;
        }

        $this->error(
            'BACKUP VERIFICATION: FAIL'
        );

        return self::FAILURE;
    }
}
