<?php

namespace App\Jobs;

use App\Models\DocumentAnalytics;
use App\Models\DocumentVersion;
use App\Services\SpreadsheetAnalyticsService;
use Illuminate\Foundation\Bus\Dispatchable;
use Throwable;

class ProcessDocumentAnalytics
{
    use Dispatchable;

    public function __construct(
        public readonly int $documentVersionId
    ) {
    }


    /**
     * Menyiapkan record pending lalu menjalankan analytics
     * setelah response HTTP dikirim ke browser.
     *
     * Tahap ini tidak membutuhkan queue worker.
     */
    public static function dispatchForVersion(
        DocumentVersion $version
    ): void {

        $extension =
            strtolower(
                (string) (
                    $version->file_extension
                    ?: pathinfo(
                        (string) $version->file_name,
                        PATHINFO_EXTENSION
                    )
                )
            );

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

        /*
        | File non-spreadsheet langsung dilewati.
        */

        if (
            ! in_array(
                $extension,
                $supportedExtensions,
                true
            )
        ) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Pending Record
        |--------------------------------------------------------------------------
        |
        | Dibuat sebelum redirect supaya halaman detail langsung mengetahui
        | bahwa analytics untuk revision ini sedang disiapkan.
        |
        */

        $analytics =
            DocumentAnalytics::query()
                ->firstOrCreate(
                    [
                        'document_version_id' =>
                            $version->id,
                    ],
                    [
                        'document_id' =>
                            $version->document_id,

                        'revision_number' =>
                            (int) $version->revision_number,

                        'status' =>
                            'pending',

                        'sheet_count' =>
                            0,

                        'total_rows' =>
                            0,

                        'total_columns' =>
                            0,

                        'numeric_cells' =>
                            0,

                        'blank_cells' =>
                            0,

                        'summary' =>
                            null,

                        'schema' =>
                            null,

                        'processed_at' =>
                            null,

                        'error_message' =>
                            null,
                    ]
                );


        /*
        | Jangan membuat proses duplicate.
        */

        if (
            in_array(
                $analytics->status,
                [
                    'ready',
                    'processing',
                ],
                true
            )
        ) {
            return;
        }


        /*
        | Bila trigger dijalankan ulang setelah failed,
        | analytics boleh dicoba kembali.
        */

        if ($analytics->status === 'failed') {

            $analytics->update([
                'status' =>
                    'pending',

                'processed_at' =>
                    null,

                'error_message' =>
                    null,
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Automatic After Response Processing
        |--------------------------------------------------------------------------
        |
        | Tidak perlu lagi:
        | php artisan smartfind:analyze-spreadsheet ID
        |
        | Tidak perlu queue worker pada Tahap 16E.
        |
        */

        self::dispatchAfterResponse(
            $version->id
        );
    }


    /**
     * Memproses satu document version.
     */
    public function handle(
        SpreadsheetAnalyticsService $service
    ): void {

        $version =
            DocumentVersion::query()
                ->find(
                    $this->documentVersionId
                );

        if ($version === null) {
            return;
        }

        try {

            $service->analyze(
                $version
            );

        } catch (Throwable $exception) {

            /*
            | SpreadsheetAnalyticsService sudah menyimpan:
            | status = failed
            | error_message = ...
            |
            | Upload dokumen tetap dianggap berhasil.
            */

            report(
                $exception
            );
        }
    }
}
