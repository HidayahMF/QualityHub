<?php

namespace App\Console\Commands;

use App\Models\DocumentAnalytics;
use App\Services\SpreadsheetAnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RetryFailedAnalytics extends Command
{
    protected $signature =
        'smartfind:retry-failed-analytics
        {--version-id= : Retry hanya document_version_id tertentu}
        {--limit= : Batas jumlah analytics failed yang diproses}
        {--dry-run : Tampilkan target tanpa menjalankan pemrosesan}';

    protected $description =
        'Retry terkontrol analytics spreadsheet berstatus failed tanpa menyentuh analytics ready.';


    public function handle(
        SpreadsheetAnalyticsService $analyticsService
    ): int {

        $defaultLimit =
            max(
                1,
                (int) config(
                    'smartfind_reliability.analytics_retry_default_limit',
                    25
                )
            );

        $maximumLimit =
            max(
                $defaultLimit,
                (int) config(
                    'smartfind_reliability.analytics_retry_max_limit',
                    100
                )
            );

        $limit =
            $this->resolveLimit(
                $defaultLimit,
                $maximumLimit
            );

        if ($limit === null) {
            return self::FAILURE;
        }

        $versionId =
            $this->resolveVersionId();

        if ($versionId === false) {
            return self::FAILURE;
        }

        $query =
            DocumentAnalytics::query()
                ->with(
                    'documentVersion'
                )
                ->where(
                    'status',
                    'failed'
                )
                ->orderBy(
                    'updated_at'
                )
                ->orderBy(
                    'id'
                );

        if (is_int($versionId)) {

            $query->where(
                'document_version_id',
                $versionId
            );
        }

        $targets =
            $query
                ->limit(
                    $limit
                )
                ->get();

        if ($targets->isEmpty()) {

            $this->info(
                'Tidak ada analytics failed yang perlu di-retry.'
            );

            return self::SUCCESS;
        }

        $this->table(
            [
                'Analytics ID',
                'Version ID',
                'Revision',
                'Error Terakhir',
            ],
            $targets
                ->map(
                    fn (DocumentAnalytics $analytics) => [
                        $analytics->id,
                        $analytics->document_version_id,
                        $analytics->revision_number,
                        mb_strimwidth(
                            (string) (
                                $analytics->error_message
                                ?? '-'
                            ),
                            0,
                            100,
                            '...'
                        ),
                    ]
                )
                ->all()
        );

        if (
            (bool) $this->option(
                'dry-run'
            )
        ) {

            $this->warn(
                'DRY RUN — tidak ada analytics yang diproses.'
            );

            return self::SUCCESS;
        }

        $success =
            0;

        $failed =
            0;

        $missingVersion =
            0;

        foreach (
            $targets
            as $analytics
        ) {

            $version =
                $analytics
                    ->documentVersion;

            if ($version === null) {

                $missingVersion++;

                $failed++;

                $this->error(
                    'Analytics ID '
                    . $analytics->id
                    . ': DocumentVersion tidak ditemukan.'
                );

                continue;
            }

            try {

                /*
                | force=true diperlukan karena ini explicit operational retry.
                | Service tetap menulis status processing/ready/failed sendiri.
                */
                $result =
                    $analyticsService
                        ->analyze(
                            $version,
                            true
                        );

                if (
                    $result->status
                    === 'ready'
                ) {

                    $success++;

                    $this->info(
                        'Version ID '
                        . $version->id
                        . ': READY'
                    );

                } else {

                    $failed++;

                    $this->error(
                        'Version ID '
                        . $version->id
                        . ': status akhir '
                        . $result->status
                    );
                }

            } catch (Throwable $exception) {

                $failed++;

                $this->error(
                    'Version ID '
                    . $version->id
                    . ': '
                    . $exception->getMessage()
                );

                report(
                    $exception
                );
            }
        }

        $this->newLine();

        $this->table(
            [
                'Berhasil',
                'Gagal',
                'Version Hilang',
            ],
            [[
                $success,
                $failed,
                $missingVersion,
            ]]
        );

        Log::info(
            'BMC QualityHub failed analytics retry completed.',
            [
                'target_count' =>
                    $targets->count(),

                'success' =>
                    $success,

                'failed' =>
                    $failed,

                'missing_version' =>
                    $missingVersion,
            ]
        );

        return $failed === 0
            ? self::SUCCESS
            : self::FAILURE;
    }


    /**
     * null berarti input limit tidak valid.
     */
    private function resolveLimit(
        int $defaultLimit,
        int $maximumLimit
    ): ?int {

        $raw =
            $this->option(
                'limit'
            );

        if (
            $raw === null
            || $raw === ''
        ) {
            return $defaultLimit;
        }

        if (
            ! ctype_digit(
                (string) $raw
            )
        ) {

            $this->error(
                '--limit harus berupa angka positif.'
            );

            return null;
        }

        $limit =
            (int) $raw;

        if (
            $limit < 1
            || $limit > $maximumLimit
        ) {

            $this->error(
                '--limit harus antara 1 dan '
                . $maximumLimit
                . '.'
            );

            return null;
        }

        return $limit;
    }


    /**
     * Return:
     * - int   = filter document_version_id
     * - null  = semua failed analytics
     * - false = input invalid
     */
    private function resolveVersionId(): int|null|false
    {
        $raw =
            $this->option(
                'version-id'
            );

        if (
            $raw === null
            || $raw === ''
        ) {
            return null;
        }

        if (
            ! ctype_digit(
                (string) $raw
            )
            || (int) $raw < 1
        ) {

            $this->error(
                '--version-id harus berupa document_version_id numerik positif.'
            );

            return false;
        }

        return (int) $raw;
    }
}
