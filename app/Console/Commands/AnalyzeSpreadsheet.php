<?php

namespace App\Console\Commands;

use App\Models\DocumentAnalytics;
use App\Models\DocumentVersion;
use App\Services\SpreadsheetAnalyticsService;
use Illuminate\Console\Command;
use Throwable;

class AnalyzeSpreadsheet extends Command
{
    protected $signature = 'smartfind:analyze-spreadsheet
                            {version? : ID pada tabel document_versions}
                            {--all : Proses semua versi spreadsheet}
                            {--force : Proses ulang analytics yang sudah ready}';

    protected $description =
        'Membaca XLSX, XLS, atau CSV dari private storage dan menyimpan hasil analytics BMC SmartFind.';


    public function handle(
        SpreadsheetAnalyticsService $service
    ): int {

        $force =
            (bool) $this->option(
                'force'
            );

        if ($this->option('all')) {
            return $this->analyzeAll(
                $service,
                $force
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

        return $this->processVersion(
            $service,
            $version,
            $force
        )
            ? self::SUCCESS
            : self::FAILURE;
    }


    private function analyzeAll(
        SpreadsheetAnalyticsService $service,
        bool $force
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
                ->whereRaw(
                    'LOWER(file_extension) IN (?, ?, ?)',
                    [
                        $supportedExtensions[0]
                            ?? 'xlsx',

                        $supportedExtensions[1]
                            ?? 'xls',

                        $supportedExtensions[2]
                            ?? 'csv',
                    ]
                )
                ->orderBy('id')
                ->get();

        if ($versions->isEmpty()) {
            $this->warn(
                'Belum ada document version spreadsheet.'
            );

            return self::SUCCESS;
        }

        $success = 0;
        $failed = 0;
        $skipped = 0;

        $bar =
            $this->output
                ->createProgressBar(
                    $versions->count()
                );

        $bar->start();

        foreach ($versions as $version) {

            $existingStatus =
                DocumentAnalytics::query()
                    ->where(
                        'document_version_id',
                        $version->id
                    )
                    ->value('status');

            if (
                ! $force
                && $existingStatus === 'ready'
            ) {
                $skipped++;
                $bar->advance();

                continue;
            }

            $ok =
                $this->processVersion(
                    $service,
                    $version,
                    $force,
                    false
                );

            if ($ok) {
                $success++;
            } else {
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();

        $this->newLine(2);

        $this->table(
            [
                'Berhasil',
                'Gagal',
                'Dilewati',
            ],
            [[
                $success,
                $failed,
                $skipped,
            ]]
        );

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }


    private function processVersion(
        SpreadsheetAnalyticsService $service,
        DocumentVersion $version,
        bool $force,
        bool $verbose = true
    ): bool {

        try {

            if ($verbose) {
                $this->info(
                    'Memproses document version ID '
                    . $version->id
                    . ' — '
                    . $version->file_name
                );
            }

            $analytics =
                $service->analyze(
                    $version,
                    $force
                );

            if ($verbose) {
                $this->table(
                    [
                        'Status',
                        'Revisi',
                        'Sheet',
                        'Baris',
                        'Kolom',
                        'Numerik',
                        'Kosong',
                    ],
                    [[
                        $analytics->status,
                        $analytics->revision_number,
                        $analytics->sheet_count,
                        $analytics->total_rows,
                        $analytics->total_columns,
                        $analytics->numeric_cells,
                        $analytics->blank_cells,
                    ]]
                );

                $this->info(
                    'Analytics spreadsheet berhasil disimpan.'
                );
            }

            return true;

        } catch (Throwable $exception) {

            if ($verbose) {
                $this->error(
                    'Analytics gagal: '
                    . $exception->getMessage()
                );
            }

            return false;
        }
    }
}
