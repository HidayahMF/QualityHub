<?php

namespace App\Console\Commands;

use App\Services\SmartFindBackupService;
use Illuminate\Console\Command;
use Throwable;

class AuditDataIntegrity extends Command
{
    protected $signature =
        'smartfind:audit-integrity
        {--details : Tampilkan seluruh record PASS/WARN/FAIL}';

    protected $description =
        'Audit read-only konsistensi database, current revision, dan private document storage BMC SmartFind.';


    public function handle(
        SmartFindBackupService $backupService
    ): int {

        try {

            $result =
                $backupService
                    ->auditCurrentIntegrity();

        } catch (Throwable $exception) {

            $this->error(
                'DATA INTEGRITY AUDIT: FAIL'
            );

            $this->line(
                $exception->getMessage()
            );

            return self::FAILURE;
        }

        $this->table(
            [
                'Status',
                'Database File Records',
                'Unique Referenced Files',
                'Physical Files',
                'Physical Bytes',
                'Warning',
                'Critical Fail',
            ],
            [[
                $result['status'],
                $result[
                    'database_file_records'
                ],
                $result[
                    'unique_referenced_files'
                ],
                $result[
                    'physical_files'
                ],
                number_format(
                    $result[
                        'physical_bytes'
                    ]
                ),
                $result[
                    'warnings'
                ],
                $result[
                    'critical_failures'
                ],
            ]]
        );

        $showDetails =
            (bool) $this->option(
                'details'
            );

        $rows =
            array_values(
                array_filter(
                    $result['rows'],
                    fn (array $row) =>
                        $showDetails
                        || $row['status']
                            !== 'PASS'
                )
            );

        if (! empty($rows)) {

            $this->newLine();

            $this->table(
                [
                    'Status',
                    'Type',
                    'Source',
                    'ID',
                    'Label',
                    'Path',
                    'Catatan',
                ],
                array_map(
                    fn (array $row) => [
                        $row['status'],
                        $row['type'],
                        $row['source'],
                        $row['source_id'],
                        $row['label'],
                        $row['path'],
                        $row['note'],
                    ],
                    $rows
                )
            );
        }

        $this->newLine();

        if (
            $result[
                'critical_failures'
            ] === 0
        ) {
            $this->info(
                'DATA INTEGRITY AUDIT: PASS'
            );
        } else {
            $this->error(
                'DATA INTEGRITY AUDIT: FAIL'
            );
        }

        $this->line(
            'Critical Fail: '
            . $result[
                'critical_failures'
            ]
            . ' | Warning: '
            . $result[
                'warnings'
            ]
        );

        if (
            $result[
                'warnings'
            ] > 0
        ) {
            $this->warn(
                'Orphan file tidak dihapus otomatis. Review manual terlebih dahulu.'
            );
        }

        return $result[
            'critical_failures'
        ] === 0
            ? self::SUCCESS
            : self::FAILURE;
    }
}
