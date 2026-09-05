<?php

namespace App\Console\Commands;

use App\Models\DocumentVersion;
use App\Services\AnalyticsAccuracyAuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

class AuditDocumentAnalytics extends Command
{
    protected $signature = 'smartfind:audit-analytics
                            {version? : ID pada tabel document_versions}
                            {--all : Audit seluruh spreadsheet version yang memiliki analytics}
                            {--details : Tampilkan detail check yang PASS juga}
                            {--json= : Simpan laporan JSON ke path yang diberikan}';

    protected $description =
        'Membandingkan analytics tersimpan dengan XLSX/XLS/CSV source secara read-only.';


    public function handle(
        AnalyticsAccuracyAuditService $auditService
    ): int {

        $includeDetails =
            (bool) $this->option(
                'details'
            );

        if ($this->option('all')) {

            return $this->auditAll(
                auditService:
                    $auditService,
                includeDetails:
                    $includeDetails
            );
        }

        $versionId =
            $this->argument(
                'version'
            );

        if (
            $versionId === null
            || ! ctype_digit(
                (string) $versionId
            )
        ) {
            $this->error(
                'Masukkan ID document_versions atau gunakan --all.'
            );

            return self::FAILURE;
        }

        $version =
            DocumentVersion::query()
                ->find(
                    (int) $versionId
                );

        if ($version === null) {
            $this->error(
                'Document version tidak ditemukan.'
            );

            return self::FAILURE;
        }

        try {

            $report =
                $auditService->audit(
                    version:
                        $version,
                    includeDetails:
                        $includeDetails
                );

        } catch (Throwable $exception) {

            $this->error(
                'Audit gagal dijalankan: '
                . $exception->getMessage()
            );

            return self::FAILURE;
        }

        $this->renderReport(
            $report,
            $includeDetails
        );

        $jsonPath =
            $this->option(
                'json'
            );

        if (
            is_string($jsonPath)
            && trim(
                $jsonPath
            ) !== ''
        ) {
            $this->writeJson(
                $report,
                trim(
                    $jsonPath
                )
            );
        }

        return (
            $report[
                'passed'
            ]
            ?? false
        )
            ? self::SUCCESS
            : self::FAILURE;
    }


    private function auditAll(
        AnalyticsAccuracyAuditService $auditService,
        bool $includeDetails
    ): int {

        $supportedExtensions =
            array_map(
                'strtolower',
                config(
                    'smartfind_analytics.supported_extensions',
                    [
                        'xlsx',
                        'xls',
                        'csv',
                    ]
                )
            );

        $versions =
            DocumentVersion::query()
                ->whereIn(
                    'id',
                    function ($query) {
                        $query
                            ->select(
                                'document_version_id'
                            )
                            ->from(
                                'document_analytics'
                            );
                    }
                )
                ->whereIn(
                    'file_extension',
                    $supportedExtensions
                )
                ->orderBy('id')
                ->get();

        if ($versions->isEmpty()) {

            $this->warn(
                'Belum ada spreadsheet analytics untuk diaudit.'
            );

            return self::SUCCESS;
        }

        $allReports = [];
        $failedAudits = 0;

        foreach (
            $versions
            as $version
        ) {
            $this->newLine();

            $this->line(
                '<fg=cyan>Audit version ID '
                . $version->id
                . ' — '
                . $version->file_name
                . '</>'
            );

            try {

                $report =
                    $auditService->audit(
                        version:
                            $version,
                        includeDetails:
                            $includeDetails
                    );

                $allReports[] =
                    $report;

                $this->renderReport(
                    $report,
                    false
                );

                if (
                    ! (
                        $report[
                            'passed'
                        ]
                        ?? false
                    )
                ) {
                    $failedAudits++;
                }

            } catch (Throwable $exception) {

                $failedAudits++;

                $allReports[] = [
                    'status' =>
                        'FAIL',

                    'document_version' => [
                        'id' =>
                            $version->id,

                        'file_name' =>
                            $version->file_name,
                    ],

                    'error' =>
                        $exception->getMessage(),
                ];

                $this->error(
                    $exception->getMessage()
                );
            }
        }

        $this->newLine();

        $this->table(
            [
                'Total Version',
                'Lulus',
                'Gagal',
            ],
            [[
                $versions->count(),
                $versions->count()
                    - $failedAudits,
                $failedAudits,
            ]]
        );

        $jsonPath =
            $this->option(
                'json'
            );

        if (
            is_string($jsonPath)
            && trim(
                $jsonPath
            ) !== ''
        ) {
            $this->writeJson(
                [
                    'status' =>
                        $failedAudits === 0
                            ? 'PASS'
                            : 'FAIL',

                    'reports' =>
                        $allReports,

                    'audited_at' =>
                        now()
                            ->toIso8601String(),
                ],
                trim(
                    $jsonPath
                )
            );
        }

        return $failedAudits === 0
            ? self::SUCCESS
            : self::FAILURE;
    }


    private function renderReport(
        array $report,
        bool $includeDetails
    ): void {

        $version =
            $report[
                'document_version'
            ]
            ?? [];

        $summary =
            $report[
                'summary'
            ]
            ?? [];

        $status =
            $report[
                'status'
            ]
            ?? 'FAIL';

        $statusLabel =
            $status === 'PASS'
                ? '<fg=green;options=bold>PASS</>'
                : '<fg=red;options=bold>FAIL</>';

        $this->newLine();

        $this->line(
            'Status : '
            . $statusLabel
        );

        $this->line(
            'Score  : '
            . number_format(
                (float) (
                    $report[
                        'score'
                    ]
                    ?? 0
                ),
                2
            )
            . '%'
        );

        $this->line(
            'Version: ID '
            . (
                $version[
                    'id'
                ]
                ?? '-'
            )
            . ' / Revisi '
            . (
                $version[
                    'revision_number'
                ]
                ?? '-'
            )
        );

        $this->line(
            'File   : '
            . (
                $version[
                    'file_name'
                ]
                ?? '-'
            )
        );

        $this->line(
            'SHA256 : '
            . (
                $report[
                    'source_sha256'
                ]
                ?? '-'
            )
        );

        $this->newLine();

        $this->table(
            [
                'PASS',
                'FAIL',
                'WARN',
                'INFO',
                'TOTAL',
            ],
            [[
                $summary[
                    'passed'
                ]
                ?? 0,

                $summary[
                    'failed'
                ]
                ?? 0,

                $summary[
                    'warnings'
                ]
                ?? 0,

                $summary[
                    'info'
                ]
                ?? 0,

                $summary[
                    'total_checks'
                ]
                ?? 0,
            ]]
        );


        /*
        |--------------------------------------------------------------------------
        | Fail / Warning / Details
        |--------------------------------------------------------------------------
        */

        $checks =
            collect(
                $report[
                    'checks'
                ]
                ?? []
            );

        $visibleChecks =
            $includeDetails
                ? $checks
                : $checks->filter(
                    fn (array $check) =>
                        in_array(
                            $check[
                                'status'
                            ]
                            ?? '',
                            [
                                'FAIL',
                                'WARN',
                                'INFO',
                            ],
                            true
                        )
                );

        if ($visibleChecks->isEmpty()) {

            $this->info(
                'Tidak ada mismatch yang ditemukan.'
            );

            return;
        }

        $rows =
            $visibleChecks
                ->map(
                    function (
                        array $check
                    ) {

                        return [
                            $check[
                                'status'
                            ]
                            ?? '-',

                            $check[
                                'scope'
                            ]
                            ?? '-',

                            $check[
                                'name'
                            ]
                            ?? '-',

                            $this->shortValue(
                                $check[
                                    'expected'
                                ]
                                ?? null
                            ),

                            $this->shortValue(
                                $check[
                                    'actual'
                                ]
                                ?? null
                            ),

                            $check[
                                'message'
                            ]
                            ?? '',
                        ];
                    }
                )
                ->values()
                ->all();

        $this->table(
            [
                'Status',
                'Scope',
                'Check',
                'Expected',
                'Actual',
                'Catatan',
            ],
            $rows
        );
    }


    private function shortValue(
        mixed $value
    ): string {

        if (
            is_array($value)
            || is_object($value)
        ) {
            $encoded =
                json_encode(
                    $value,
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                );

            if (! is_string($encoded)) {
                return '[complex]';
            }

            return mb_strimwidth(
                $encoded,
                0,
                80,
                '…'
            );
        }

        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value
                ? 'true'
                : 'false';
        }

        return mb_strimwidth(
            (string) $value,
            0,
            80,
            '…'
        );
    }


    private function writeJson(
        array $report,
        string $path
    ): void {

        $fullPath =
            $this->resolveOutputPath(
                $path
            );

        $directory =
            dirname(
                $fullPath
            );

        if (! is_dir($directory)) {
            File::makeDirectory(
                $directory,
                0755,
                true
            );
        }

        File::put(
            $fullPath,
            json_encode(
                $report,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            )
        );

        $this->info(
            'Laporan JSON disimpan: '
            . $fullPath
        );
    }


    private function resolveOutputPath(
        string $path
    ): string {

        /*
        | Absolute Windows path:
        | D:\folder\audit.json
        */

        if (
            preg_match(
                '/^[A-Za-z]:[\\\\\/]/',
                $path
            ) === 1
        ) {
            return $path;
        }

        /*
        | Absolute Unix path.
        */

        if (
            str_starts_with(
                $path,
                '/'
            )
        ) {
            return $path;
        }

        return base_path(
            $path
        );
    }
}
