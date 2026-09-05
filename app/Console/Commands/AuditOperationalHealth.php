<?php

namespace App\Console\Commands;

use App\Services\OperationalHealthService;
use Illuminate\Console\Command;
use Throwable;

class AuditOperationalHealth extends Command
{
    protected $signature =
        'smartfind:audit-health
        {--details : Tampilkan seluruh check termasuk yang PASS}';

    protected $description =
        'Audit read-only database, runtime, storage, queue, analytics, backup, dan maintenance BMC QualityHub.';


    public function handle(
        OperationalHealthService $healthService
    ): int {

        try {

            $report =
                $healthService
                    ->audit();

        } catch (Throwable $exception) {

            $this->error(
                'OPERATIONAL HEALTH AUDIT: FAIL'
            );

            $this->line(
                $exception->getMessage()
            );

            return self::FAILURE;
        }

        $showDetails =
            (bool) $this->option(
                'details'
            );

        $rows =
            collect(
                $report[
                    'checks'
                ]
            )
                ->filter(
                    fn (array $check) =>
                        $showDetails
                        || (
                            $check[
                                'status'
                            ]
                            ?? 'FAIL'
                        ) !== 'PASS'
                )
                ->map(
                    fn (array $check) => [
                        $check[
                            'status'
                        ],
                        $check[
                            'category'
                        ],
                        $check[
                            'name'
                        ],
                        $check[
                            'note'
                        ],
                    ]
                )
                ->values()
                ->all();

        if ($rows !== []) {

            $this->table(
                [
                    'Status',
                    'Area',
                    'Check',
                    'Catatan',
                ],
                $rows
            );

        } else {

            $this->info(
                'Semua operational health check PASS.'
            );
        }

        $this->newLine();

        if (
            (
                $report[
                    'critical_failures'
                ]
                ?? 1
            ) === 0
        ) {

            $this->info(
                'OPERATIONAL HEALTH AUDIT: PASS'
            );

        } else {

            $this->error(
                'OPERATIONAL HEALTH AUDIT: FAIL'
            );
        }

        $this->line(
            'Critical Fail: '
            . (
                $report[
                    'critical_failures'
                ]
                ?? 0
            )
            . ' | Warning: '
            . (
                $report[
                    'warnings'
                ]
                ?? 0
            )
        );

        return (
            $report[
                'critical_failures'
            ]
            ?? 1
        ) === 0
            ? self::SUCCESS
            : self::FAILURE;
    }
}
