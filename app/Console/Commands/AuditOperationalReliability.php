<?php

namespace App\Console\Commands;

use App\Models\DocumentAnalytics;
use App\Services\OperationalHealthService;
use App\Services\SpreadsheetAnalyticsService;
use Illuminate\Console\Command;
use ReflectionMethod;
use Throwable;

class AuditOperationalReliability extends Command
{
    protected $signature =
        'smartfind:audit-reliability';

    protected $description =
        'Final read-only audit Tahap 17H Operational Reliability & Failure Handling BMC QualityHub.';


    public function handle(
        OperationalHealthService $healthService
    ): int {

        $checks = [];

        $this->checkHealthService(
            $checks,
            $healthService
        );

        $this->checkCommands(
            $checks
        );

        $this->checkConfiguration(
            $checks
        );

        $this->checkAnalyticsContract(
            $checks
        );

        $this->checkRegressionTest(
            $checks
        );

        $this->table(
            [
                'Status',
                'Check',
                'Catatan',
            ],
            array_map(
                fn (array $check) => [
                    $check[
                        'status'
                    ],

                    $check[
                        'name'
                    ],

                    $check[
                        'note'
                    ],
                ],
                $checks
            )
        );

        $criticalFailures =
            collect(
                $checks
            )
                ->where(
                    'status',
                    'FAIL'
                )
                ->count();

        $warnings =
            collect(
                $checks
            )
                ->where(
                    'status',
                    'WARN'
                )
                ->count();

        $this->newLine();

        if ($criticalFailures === 0) {

            $this->info(
                'OPERATIONAL RELIABILITY AUDIT: PASS'
            );

        } else {

            $this->error(
                'OPERATIONAL RELIABILITY AUDIT: FAIL'
            );
        }

        $this->line(
            'Critical Fail: '
            . $criticalFailures
            . ' | Warning: '
            . $warnings
        );

        return $criticalFailures === 0
            ? self::SUCCESS
            : self::FAILURE;
    }


    private function checkHealthService(
        array &$checks,
        OperationalHealthService $healthService
    ): void {

        try {

            $report =
                $healthService
                    ->audit();

            $criticalFailures =
                (int) (
                    $report[
                        'critical_failures'
                    ]
                    ?? 1
                );

            $warnings =
                (int) (
                    $report[
                        'warnings'
                    ]
                    ?? 0
                );

            $this->addCheck(
                checks:
                    $checks,

                name:
                    'Operational health runtime',

                passed:
                    $criticalFailures === 0,

                note:
                    'Critical Fail: '
                    . $criticalFailures
                    . ' | Warning: '
                    . $warnings
            );

            if ($warnings > 0) {

                $checks[] = [
                    'status' =>
                        'WARN',

                    'name' =>
                        'Operational health warning',

                    'note' =>
                        'Ada '
                        . $warnings
                        . ' warning non-kritis. Jalankan smartfind:audit-health --details.',
                ];
            }

        } catch (Throwable $exception) {

            $this->addCheck(
                checks:
                    $checks,

                name:
                    'Operational health runtime',

                passed:
                    false,

                note:
                    $exception->getMessage()
            );
        }
    }


    private function checkCommands(
        array &$checks
    ): void {

        $application =
            $this->getApplication();

        if ($application === null) {

            $this->addCheck(
                checks:
                    $checks,

                name:
                    'Operational commands registered',

                passed:
                    false,

                note:
                    'Console application tidak tersedia.'
            );

            return;
        }

        foreach (
            [
                'smartfind:audit-health',
                'smartfind:retry-failed-analytics',
                'smartfind:audit-reliability',
            ]
            as $commandName
        ) {

            $this->addCheck(
                checks:
                    $checks,

                name:
                    'Command '
                    . $commandName,

                passed:
                    $application->has(
                        $commandName
                    ),

                note:
                    $application->has(
                        $commandName
                    )
                        ? 'Command terdaftar.'
                        : 'Command tidak ditemukan.'
            );
        }

        if (
            $application->has(
                'smartfind:retry-failed-analytics'
            )
        ) {

            $command =
                $application->find(
                    'smartfind:retry-failed-analytics'
                );

            $definition =
                $command
                    ->getDefinition();

            $requiredOptions = [
                'version-id',
                'limit',
                'dry-run',
            ];

            foreach (
                $requiredOptions
                as $option
            ) {

                $this->addCheck(
                    checks:
                        $checks,

                    name:
                        'Retry option --'
                        . $option,

                    passed:
                        $definition
                            ->hasOption(
                                $option
                            ),

                    note:
                        $definition
                            ->hasOption(
                                $option
                            )
                            ? 'Option tersedia.'
                            : 'Option tidak ditemukan.'
                );
            }
        }
    }


    private function checkConfiguration(
        array &$checks
    ): void {

        $requiredKeys = [
            'private_storage_warning_free_bytes',
            'private_storage_critical_free_bytes',
            'analytics_pending_stale_minutes',
            'analytics_processing_stale_minutes',
            'queue_pending_warning_count',
            'backup_warning_age_hours',
            'backup_required_in_production',
            'analytics_retry_default_limit',
            'analytics_retry_max_limit',
        ];

        foreach (
            $requiredKeys
            as $key
        ) {

            $value =
                config(
                    'smartfind_reliability.'
                    . $key
                );

            $this->addCheck(
                checks:
                    $checks,

                name:
                    'Reliability config '
                    . $key,

                passed:
                    $value !== null,

                note:
                    $value !== null
                        ? 'Config tersedia.'
                        : 'Config belum tersedia.'
            );
        }
    }


    private function checkAnalyticsContract(
        array &$checks
    ): void {

        try {

            $method =
                new ReflectionMethod(
                    SpreadsheetAnalyticsService::class,
                    'analyze'
                );

            $parameters =
                $method
                    ->getParameters();

            $forceSupported =
                isset(
                    $parameters[
                        1
                    ]
                )
                && $parameters[
                    1
                ]->getName()
                    === 'force';

            $this->addCheck(
                checks:
                    $checks,

                name:
                    'Spreadsheet analytics force retry contract',

                passed:
                    $forceSupported,

                note:
                    $forceSupported
                        ? 'analyze(DocumentVersion, bool $force) tersedia.'
                        : 'Parameter $force tidak ditemukan.'
            );

        } catch (Throwable $exception) {

            $this->addCheck(
                checks:
                    $checks,

                name:
                    'Spreadsheet analytics force retry contract',

                passed:
                    false,

                note:
                    $exception->getMessage()
            );
        }

        $this->addCheck(
            checks:
                $checks,

            name:
                'DocumentAnalytics documentVersion relation',

            passed:
                method_exists(
                    DocumentAnalytics::class,
                    'documentVersion'
                ),

            note:
                method_exists(
                    DocumentAnalytics::class,
                    'documentVersion'
                )
                    ? 'Relasi tersedia.'
                    : 'Relasi documentVersion tidak ditemukan.'
        );
    }


    private function checkRegressionTest(
        array &$checks
    ): void {

        $path =
            base_path(
                'tests/Feature/OperationalReliabilityTest.php'
            );

        $this->addCheck(
            checks:
                $checks,

            name:
                'Operational reliability regression test',

            passed:
                is_file(
                    $path
                ),

            note:
                is_file(
                    $path
                )
                    ? 'Regression test tersedia.'
                    : 'OperationalReliabilityTest.php tidak ditemukan.'
        );
    }


    private function addCheck(
        array &$checks,
        string $name,
        bool $passed,
        string $note
    ): void {

        $checks[] = [
            'status' =>
                $passed
                    ? 'PASS'
                    : 'FAIL',

            'name' =>
                $name,

            'note' =>
                $note,
        ];
    }
}
