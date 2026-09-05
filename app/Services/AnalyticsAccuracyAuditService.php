<?php

namespace App\Services;

use App\Models\DocumentAnalytics;
use App\Models\DocumentAnalyticSheet;
use App\Models\DocumentVersion;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use Throwable;

class AnalyticsAccuracyAuditService
{
    public function __construct(
        private readonly PrivateDocumentStorageService $privateStorage
    ) {
    }


    /**
     * Audit hanya membaca source spreadsheet + hasil analytics.
     * Tidak ada data yang diubah.
     */
    public function audit(
        DocumentVersion $version,
        bool $includeDetails = false
    ): array {

        $checks = [];
        $sheetReports = [];

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

        if (
            ! in_array(
                $extension,
                $supportedExtensions,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Document version ini bukan spreadsheet XLSX/XLS/CSV.'
            );
        }

        $diskName =
            (string) config(
                'smartfind_analytics.disk',
                'bmc_documents'
            );

        $filePath =
            (string) $version->file_path;

        $this->addCheck(
            $checks,
            scope:
                'Source File',
            name:
                'Path database berbentuk relative path aman',
            expected:
                true,
            actual:
                $this->privateStorage
                    ->isSafeRelativePath(
                        $filePath
                    ),
            severity:
                'error'
        );

        $absolutePath =
            $this->privateStorage
                ->resolveExistingFile(
                    $filePath,
                    $diskName
                );

        $this->addCheck(
            $checks,
            scope:
                'Source File',
            name:
                'File tersedia dan realpath tetap di dalam private storage',
            expected:
                true,
            actual:
                $absolutePath !== null,
            severity:
                'error'
        );

        if ($absolutePath === null) {
            return $this->finalizeReport(
                version:
                    $version,
                checks:
                    $checks,
                sheetReports:
                    $sheetReports,
                sourceHash:
                    null
            );
        }

        $actualFileSize =
            filesize(
                $absolutePath
            );

        $sourceHash =
            hash_file(
                'sha256',
                $absolutePath
            );

        $this->addCheck(
            $checks,
            scope:
                'Source File',
            name:
                'Ukuran file source cocok dengan document_versions',
            expected:
                (int) $version->file_size,
            actual:
                $actualFileSize === false
                    ? null
                    : (int) $actualFileSize,
            severity:
                'error'
        );


        /*
        |--------------------------------------------------------------------------
        | Stored Analytics
        |--------------------------------------------------------------------------
        */

        $analytics =
            DocumentAnalytics::query()
                ->with([
                    'sheets',
                ])
                ->where(
                    'document_version_id',
                    $version->id
                )
                ->first();

        $this->addCheck(
            $checks,
            scope:
                'Revision Binding',
            name:
                'Analytics record tersedia',
            expected:
                true,
            actual:
                $analytics !== null,
            severity:
                'error'
        );

        if ($analytics === null) {
            return $this->finalizeReport(
                version:
                    $version,
                checks:
                    $checks,
                sheetReports:
                    $sheetReports,
                sourceHash:
                    $sourceHash
            );
        }

        $this->addCheck(
            $checks,
            scope:
                'Revision Binding',
            name:
                'Status analytics siap',
            expected:
                'ready',
            actual:
                $analytics->status,
            severity:
                'error'
        );

        $this->addCheck(
            $checks,
            scope:
                'Revision Binding',
            name:
                'document_id cocok',
            expected:
                (int) $version->document_id,
            actual:
                (int) $analytics->document_id,
            severity:
                'error'
        );

        $this->addCheck(
            $checks,
            scope:
                'Revision Binding',
            name:
                'document_version_id cocok',
            expected:
                (int) $version->id,
            actual:
                (int) $analytics->document_version_id,
            severity:
                'error'
        );

        $this->addCheck(
            $checks,
            scope:
                'Revision Binding',
            name:
                'Nomor revisi cocok',
            expected:
                (int) $version->revision_number,
            actual:
                (int) $analytics->revision_number,
            severity:
                'error'
        );


        /*
        |--------------------------------------------------------------------------
        | Summary Contract
        |--------------------------------------------------------------------------
        */

        $summary =
            is_array(
                $analytics->summary
            )
                ? $analytics->summary
                : [];

        $this->addCheck(
            $checks,
            scope:
                'Summary',
            name:
                'Nama file summary cocok',
            expected:
                (string) $version->file_name,
            actual:
                $summary['file_name']
                ?? null,
            severity:
                'error'
        );

        $this->addCheck(
            $checks,
            scope:
                'Summary',
            name:
                'Extension summary cocok',
            expected:
                $extension,
            actual:
                strtolower(
                    (string) (
                        $summary[
                            'file_extension'
                        ]
                        ?? ''
                    )
                ),
            severity:
                'error'
        );

        $this->addCheck(
            $checks,
            scope:
                'Summary',
            name:
                'Ukuran file summary cocok',
            expected:
                (int) $version->file_size,
            actual:
                (int) (
                    $summary[
                        'file_size'
                    ]
                    ?? -1
                ),
            severity:
                'error'
        );

        $this->addCheck(
            $checks,
            scope:
                'Summary',
            name:
                'Nomor revisi summary cocok',
            expected:
                (int) $version->revision_number,
            actual:
                (int) (
                    $summary[
                        'revision_number'
                    ]
                    ?? -1
                ),
            severity:
                'error'
        );


        /*
        |--------------------------------------------------------------------------
        | Load Source Workbook
        |--------------------------------------------------------------------------
        */

        $spreadsheet =
            $this->loadSpreadsheet(
                $absolutePath,
                $extension
            );

        try {

            $sourceSheetCount =
                $spreadsheet
                    ->getSheetCount();

            $this->addCheck(
                $checks,
                scope:
                    'Workbook',
                name:
                    'Jumlah sheet source cocok',
                expected:
                    $sourceSheetCount,
                actual:
                    (int) $analytics->sheet_count,
                severity:
                    'error'
            );

            $this->addCheck(
                $checks,
                scope:
                    'Workbook',
                name:
                    'Jumlah record analytic sheet cocok',
                expected:
                    $sourceSheetCount,
                actual:
                    $analytics
                        ->sheets
                        ->count(),
                severity:
                    'error'
            );


            $schemaSheets =
                collect(
                    $analytics->schema[
                        'sheets'
                    ]
                    ?? []
                )
                    ->keyBy(
                        fn (array $sheet) =>
                            (int) (
                                $sheet[
                                    'sheet_index'
                                ]
                                ?? -1
                            )
                    );

            $workbookTotals = [
                'total_rows' =>
                    0,

                'total_columns' =>
                    0,

                'numeric_cells' =>
                    0,

                'blank_cells' =>
                    0,
            ];

            foreach (
                $spreadsheet
                    ->getWorksheetIterator()
                as $sheetIndex => $worksheet
            ) {
                $sheetIndex =
                    (int) $sheetIndex;

                $storedSheet =
                    $analytics
                        ->sheets
                        ->firstWhere(
                            'sheet_index',
                            $sheetIndex
                        );

                $schemaSheet =
                    $schemaSheets
                        ->get(
                            $sheetIndex
                        );

                $sheetReport =
                    $this->auditSheet(
                        worksheet:
                            $worksheet,
                        sheetIndex:
                            $sheetIndex,
                        storedSheet:
                            $storedSheet,
                        schemaSheet:
                            is_array(
                                $schemaSheet
                            )
                                ? $schemaSheet
                                : null,
                        includeDetails:
                            $includeDetails
                    );

                $sheetReports[] =
                    $sheetReport;

                foreach (
                    $sheetReport[
                        'checks'
                    ]
                    as $sheetCheck
                ) {
                    $checks[] =
                        $sheetCheck;
                }

                $workbookTotals[
                    'total_rows'
                ] +=
                    (int) (
                        $sheetReport[
                            'source_metrics'
                        ][
                            'row_count'
                        ]
                        ?? 0
                    );

                $workbookTotals[
                    'total_columns'
                ] +=
                    (int) (
                        $sheetReport[
                            'source_metrics'
                        ][
                            'column_count'
                        ]
                        ?? 0
                    );

                $workbookTotals[
                    'numeric_cells'
                ] +=
                    (int) (
                        $sheetReport[
                            'source_metrics'
                        ][
                            'numeric_cells'
                        ]
                        ?? 0
                    );

                $workbookTotals[
                    'blank_cells'
                ] +=
                    (int) (
                        $sheetReport[
                            'source_metrics'
                        ][
                            'blank_cells'
                        ]
                        ?? 0
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | Workbook Aggregates
            |--------------------------------------------------------------------------
            */

            foreach (
                [
                    'total_rows' =>
                        'Total baris workbook',

                    'total_columns' =>
                        'Total kolom workbook',

                    'numeric_cells' =>
                        'Total sel numerik',

                    'blank_cells' =>
                        'Total sel kosong',
                ]
                as $field => $label
            ) {
                $this->addCheck(
                    $checks,
                    scope:
                        'Workbook',
                    name:
                        $label,
                    expected:
                        $workbookTotals[
                            $field
                        ],
                    actual:
                        (int) $analytics->{$field},
                    severity:
                        'error'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Sheet Names Summary
            |--------------------------------------------------------------------------
            */

            $sourceSheetNames = [];

            foreach (
                $spreadsheet
                    ->getWorksheetIterator()
                as $worksheet
            ) {
                $sourceSheetNames[] =
                    $worksheet
                        ->getTitle();
            }

            $this->addCheck(
                $checks,
                scope:
                    'Summary',
                name:
                    'Daftar nama sheet summary cocok',
                expected:
                    $sourceSheetNames,
                actual:
                    array_values(
                        $summary[
                            'sheet_names'
                        ]
                        ?? []
                    ),
                severity:
                    'error'
            );

        } finally {

            $spreadsheet
                ->disconnectWorksheets();

            unset($spreadsheet);
        }


        /*
        |--------------------------------------------------------------------------
        | Source Integrity Trace
        |--------------------------------------------------------------------------
        |
        | SHA-256 dicetak sebagai fingerprint audit saat ini.
        | Schema analytics sekarang belum menyimpan source hash historis,
        | jadi hash ini bersifat traceability, bukan pembuktian tamper history.
        |
        */

        $this->addCheck(
            $checks,
            scope:
                'Integrity',
            name:
                'SHA-256 source berhasil dihitung',
            expected:
                true,
            actual:
                is_string($sourceHash)
                && strlen($sourceHash) === 64,
            severity:
                'error'
        );

        $this->addCheck(
            $checks,
            scope:
                'Integrity',
            name:
                'Hash historis tersimpan di analytics',
            expected:
                true,
            actual:
                false,
            severity:
                'info',
            message:
                'Saat ini source hash belum disimpan di database. '
                . 'Audit tetap memverifikasi source saat ini secara langsung.'
        );


        return $this->finalizeReport(
            version:
                $version,
            checks:
                $checks,
            sheetReports:
                $sheetReports,
            sourceHash:
                $sourceHash
        );
    }


    /**
     * Audit satu sheet terhadap source asli.
     */
    private function auditSheet(
        Worksheet $worksheet,
        int $sheetIndex,
        ?DocumentAnalyticSheet $storedSheet,
        ?array $schemaSheet,
        bool $includeDetails
    ): array {

        $checks = [];

        $sourceTitle =
            $worksheet
                ->getTitle();

        $this->addCheck(
            $checks,
            scope:
                'Sheet ' . ($sheetIndex + 1),
            name:
                'Record sheet tersedia',
            expected:
                true,
            actual:
                $storedSheet !== null,
            severity:
                'error'
        );

        $this->addCheck(
            $checks,
            scope:
                'Sheet ' . ($sheetIndex + 1),
            name:
                'Schema sheet tersedia',
            expected:
                true,
            actual:
                $schemaSheet !== null,
            severity:
                'error'
        );

        if (
            $storedSheet === null
            || $schemaSheet === null
        ) {
            return [
                'sheet_index' =>
                    $sheetIndex,

                'sheet_name' =>
                    $sourceTitle,

                'checks' =>
                    $checks,

                'source_metrics' => [
                    'row_count' =>
                        0,

                    'column_count' =>
                        0,

                    'numeric_cells' =>
                        0,

                    'blank_cells' =>
                        0,
                ],

                'details' =>
                    [],
            ];
        }

        $this->addCheck(
            $checks,
            scope:
                'Sheet ' . ($sheetIndex + 1),
            name:
                'Nama sheet cocok',
            expected:
                $sourceTitle,
            actual:
                $storedSheet->sheet_name,
            severity:
                'error'
        );

        $highestDataRow =
            max(
                1,
                (int) $worksheet
                    ->getHighestDataRow()
            );

        $highestDataColumn =
            $worksheet
                ->getHighestDataColumn();

        $maxColumn =
            max(
                1,
                Coordinate::columnIndexFromString(
                    $highestDataColumn
                )
            );

        $headerRow =
            (int) (
                $schemaSheet[
                    'header_row'
                ]
                ?? 0
            );

        $this->addCheck(
            $checks,
            scope:
                'Sheet ' . ($sheetIndex + 1),
            name:
                'Header row berada dalam range source',
            expected:
                true,
            actual:
                $headerRow >= 0
                && $headerRow <= $highestDataRow,
            severity:
                'error'
        );

        if (
            $headerRow < 0
            || $headerRow > $highestDataRow
        ) {
            return [
                'sheet_index' =>
                    $sheetIndex,

                'sheet_name' =>
                    $sourceTitle,

                'checks' =>
                    $checks,

                'source_metrics' => [
                    'row_count' =>
                        0,

                    'column_count' =>
                        $maxColumn,

                    'numeric_cells' =>
                        0,

                    'blank_cells' =>
                        0,
                ],

                'details' =>
                    [],
            ];
        }

        $sourceHeaders =
            $this->buildHeaders(
                $worksheet,
                $headerRow,
                $maxColumn
            );

        $storedHeaders =
            array_values(
                $storedSheet->headers
                ?? []
            );

        /*
        |--------------------------------------------------------------------------
        | Header Contract
        |--------------------------------------------------------------------------
        |
        | Parser menyimpan document_analytic_sheets.headers sebagai array
        | nama header saja, contoh:
        |
        | ["ALGORITMA", "ACCURACY"]
        |
        | Sedangkan $sourceHeaders di audit juga membawa metadata internal
        | index + letter untuk keperluan pemeriksaan kolom.
        |
        | Karena itu yang dibandingkan harus nama header source saja,
        | bukan struktur metadata audit.
        |
        */

        $sourceHeaderNames =
            array_values(
                array_map(
                    fn (array $header) =>
                        $header['name'],
                    $sourceHeaders
                )
            );

        $this->addCheck(
            $checks,
            scope:
                'Sheet ' . ($sheetIndex + 1),
            name:
                'Header source cocok',
            expected:
                $sourceHeaderNames,
            actual:
                $storedHeaders,
            severity:
                'error'
        );

        $headerPlausibility =
            $this->auditHeaderPlausibility(
                $worksheet,
                $headerRow,
                $maxColumn
            );

        $this->addCheck(
            $checks,
            scope:
                'Sheet ' . ($sheetIndex + 1),
            name:
                'Header terdeteksi secara masuk akal',
            expected:
                true,
            actual:
                $headerPlausibility[
                    'plausible'
                ],
            severity:
                'warning',
            message:
                $headerPlausibility[
                    'message'
                ]
        );

        $sourceStatistics =
            $this->initializeStatistics(
                $sourceHeaders
            );

        $previewData = [];

        $previewLimit =
            max(
                1,
                (int) config(
                    'smartfind_analytics.preview_rows',
                    20
                )
            );

        $rowCount = 0;
        $numericCells = 0;
        $blankCells = 0;

        for (
            $row = $headerRow + 1;
            $row <= $highestDataRow;
            $row++
        ) {
            $typedCells = [];
            $rowHasData = false;

            foreach (
                $sourceHeaders
                as $columnOffset => $header
            ) {
                $cell =
                    $worksheet
                        ->getCell(
                            $header[
                                'letter'
                            ]
                            . $row
                        );

                $typed =
                    $this->extractCellValue(
                        $cell
                    );

                $typedCells[
                    $columnOffset
                ] = $typed;

                if (
                    $typed[
                        'type'
                    ] !== 'blank'
                ) {
                    $rowHasData = true;
                }
            }

            if (! $rowHasData) {
                continue;
            }

            $rowCount++;

            $previewRow = [];

            foreach (
                $sourceHeaders
                as $columnOffset => $header
            ) {
                $typed =
                    $typedCells[
                        $columnOffset
                    ];

                $previewRow[
                    $header[
                        'name'
                    ]
                ] =
                    $typed[
                        'value'
                    ];

                $this->accumulateStatistics(
                    $sourceStatistics[
                        $columnOffset
                    ],
                    $typed
                );

                if (
                    $typed[
                        'type'
                    ] === 'numeric'
                ) {
                    $numericCells++;
                }

                if (
                    $typed[
                        'type'
                    ] === 'blank'
                ) {
                    $blankCells++;
                }
            }

            if (
                count(
                    $previewData
                ) < $previewLimit
            ) {
                $previewData[] =
                    $previewRow;
            }
        }

        $sourceStatistics =
            $this->finalizeStatistics(
                $sourceStatistics
            );


        /*
        |--------------------------------------------------------------------------
        | Structural Checks
        |--------------------------------------------------------------------------
        */

        $this->addCheck(
            $checks,
            scope:
                'Sheet ' . ($sheetIndex + 1),
            name:
                'Jumlah baris data',
            expected:
                $rowCount,
            actual:
                (int) $storedSheet->row_count,
            severity:
                'error'
        );

        $this->addCheck(
            $checks,
            scope:
                'Sheet ' . ($sheetIndex + 1),
            name:
                'Jumlah kolom',
            expected:
                $maxColumn,
            actual:
                (int) $storedSheet->column_count,
            severity:
                'error'
        );

        $this->addCheck(
            $checks,
            scope:
                'Sheet ' . ($sheetIndex + 1),
            name:
                'Preview data cocok dengan source',
            expected:
                $previewData,
            actual:
                array_values(
                    $storedSheet->preview_data
                    ?? []
                ),
            severity:
                'error'
        );


        /*
        |--------------------------------------------------------------------------
        | Column Statistics
        |--------------------------------------------------------------------------
        */

        $storedStatistics =
            array_values(
                $storedSheet->statistics
                ?? []
            );

        $this->addCheck(
            $checks,
            scope:
                'Sheet ' . ($sheetIndex + 1),
            name:
                'Jumlah statistik kolom',
            expected:
                count(
                    $sourceStatistics
                ),
            actual:
                count(
                    $storedStatistics
                ),
            severity:
                'error'
        );

        foreach (
            $sourceStatistics
            as $columnIndex => $sourceColumn
        ) {
            $storedColumn =
                $storedStatistics[
                    $columnIndex
                ]
                ?? null;

            if (! is_array($storedColumn)) {

                $this->addCheck(
                    $checks,
                    scope:
                        'Sheet '
                        . ($sheetIndex + 1),
                    name:
                        'Statistik kolom '
                        . $sourceColumn[
                            'header'
                        ],
                    expected:
                        'tersedia',
                    actual:
                        'tidak tersedia',
                    severity:
                        'error'
                );

                continue;
            }

            $columnScope =
                'Sheet '
                . ($sheetIndex + 1)
                . ' / '
                . $sourceColumn[
                    'header'
                ];

            foreach (
                [
                    'header',
                    'letter',
                    'type',
                    'non_empty_count',
                    'blank_count',
                    'numeric_count',
                    'date_count',
                    'text_count',
                    'boolean_count',
                    'unique_count',
                    'unique_count_truncated',
                ]
                as $field
            ) {
                $this->addCheck(
                    $checks,
                    scope:
                        $columnScope,
                    name:
                        $field,
                    expected:
                        $sourceColumn[
                            $field
                        ]
                        ?? null,
                    actual:
                        $storedColumn[
                            $field
                        ]
                        ?? null,
                    severity:
                        'error'
                );
            }

            $this->compareNumericSummary(
                checks:
                    $checks,
                scope:
                    $columnScope,
                expected:
                    $sourceColumn[
                        'numeric'
                    ]
                    ?? null,
                actual:
                    $storedColumn[
                        'numeric'
                    ]
                    ?? null
            );

            $this->addCheck(
                $checks,
                scope:
                    $columnScope,
                name:
                    'Rentang tanggal',
                expected:
                    $sourceColumn[
                        'date'
                    ]
                    ?? null,
                actual:
                    $storedColumn[
                        'date'
                    ]
                    ?? null,
                severity:
                    'error'
            );

            $this->addCheck(
                $checks,
                scope:
                    $columnScope,
                name:
                    'Top values',
                expected:
                    $sourceColumn[
                        'top_values'
                    ]
                    ?? [],
                actual:
                    $storedColumn[
                        'top_values'
                    ]
                    ?? [],
                severity:
                    'error'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Chart Contract
        |--------------------------------------------------------------------------
        */

        $chartAudit =
            $this->auditChartSuggestions(
                storedSheet:
                    $storedSheet,
                statistics:
                    $storedStatistics
            );

        foreach (
            $chartAudit[
                'checks'
            ]
            as $chartCheck
        ) {
            $checks[] =
                $chartCheck;
        }


        return [
            'sheet_index' =>
                $sheetIndex,

            'sheet_name' =>
                $sourceTitle,

            'checks' =>
                $checks,

            'source_metrics' => [
                'row_count' =>
                    $rowCount,

                'column_count' =>
                    $maxColumn,

                'numeric_cells' =>
                    $numericCells,

                'blank_cells' =>
                    $blankCells,
            ],

            'details' =>
                $includeDetails
                    ? [
                        'header_row' =>
                            $headerRow,

                        'headers' =>
                            $sourceHeaders,

                        'preview_rows' =>
                            count(
                                $previewData
                            ),

                        'chart_suggestions' =>
                            $storedSheet
                                ->chart_suggestions
                                ?? [],
                    ]
                    : [],
        ];
    }


    /**
     * Memastikan suggestion chart tidak mereferensikan kolom/tipe salah.
     */
    private function auditChartSuggestions(
        DocumentAnalyticSheet $storedSheet,
        array $statistics
    ): array {

        $checks = [];

        $columns =
            collect(
                $statistics
            )
                ->filter(
                    fn ($column) =>
                        is_array(
                            $column
                        )
                        && isset(
                            $column[
                                'header'
                            ]
                        )
                )
                ->keyBy(
                    'header'
                );

        $suggestions =
            array_values(
                $storedSheet
                    ->chart_suggestions
                ?? []
            );

        $scope =
            'Sheet '
            . (
                (int) $storedSheet
                    ->sheet_index
                + 1
            )
            . ' / Chart';

        $this->addCheck(
            $checks,
            scope:
                $scope,
            name:
                'Jumlah saran chart dalam batas',
            expected:
                true,
            actual:
                count(
                    $suggestions
                ) <= 4,
            severity:
                'error'
        );

        foreach (
            $suggestions
            as $index => $suggestion
        ) {
            if (! is_array($suggestion)) {
                $this->addCheck(
                    $checks,
                    scope:
                        $scope,
                    name:
                        'Chart '
                        . ($index + 1)
                        . ' memiliki struktur valid',
                    expected:
                        true,
                    actual:
                        false,
                    severity:
                        'error'
                );

                continue;
            }

            $type =
                $suggestion[
                    'type'
                ]
                ?? null;

            $labelColumn =
                $suggestion[
                    'label_column'
                ]
                ?? null;

            $valueColumn =
                $suggestion[
                    'value_column'
                ]
                ?? null;

            $labelStats =
                $columns
                    ->get(
                        $labelColumn
                    );

            $valueStats =
                $valueColumn !== null
                    ? $columns
                        ->get(
                            $valueColumn
                        )
                    : null;

            $valid =
                match ($type) {

                    'line' =>
                        is_array(
                            $labelStats
                        )
                        && (
                            $labelStats[
                                'type'
                            ]
                            ?? null
                        ) === 'date'
                        && is_array(
                            $valueStats
                        )
                        && (
                            $valueStats[
                                'type'
                            ]
                            ?? null
                        ) === 'numeric'
                        && (
                            $suggestion[
                                'aggregation'
                            ]
                            ?? null
                        ) === 'sum',

                    'bar' =>
                        is_array(
                            $labelStats
                        )
                        && (
                            $labelStats[
                                'type'
                            ]
                            ?? null
                        ) === 'categorical'
                        && is_array(
                            $valueStats
                        )
                        && (
                            $valueStats[
                                'type'
                            ]
                            ?? null
                        ) === 'numeric'
                        && (
                            $suggestion[
                                'aggregation'
                            ]
                            ?? null
                        ) === 'sum',

                    'donut' =>
                        is_array(
                            $labelStats
                        )
                        && (
                            $labelStats[
                                'type'
                            ]
                            ?? null
                        ) === 'categorical'
                        && $valueColumn === null
                        && (
                            $suggestion[
                                'aggregation'
                            ]
                            ?? null
                        ) === 'count',

                    default =>
                        false,
                };

            $this->addCheck(
                $checks,
                scope:
                    $scope,
                name:
                    'Chart '
                    . ($index + 1)
                    . ' sesuai tipe kolom',
                expected:
                    true,
                actual:
                    $valid,
                severity:
                    'error',
                message:
                    (string) (
                        $suggestion[
                            'title'
                        ]
                        ?? ''
                    )
            );
        }

        return [
            'checks' =>
                $checks,
        ];
    }


    /**
     * Pemeriksaan konservatif untuk mendeteksi header yang tampak janggal.
     * Ini warning, bukan penentu ulang header parser.
     */
    private function auditHeaderPlausibility(
        Worksheet $worksheet,
        int $headerRow,
        int $maxColumn
    ): array {

        if ($headerRow === 0) {
            return [
                'plausible' =>
                    true,

                'message' =>
                    'Parser menandai sheet tanpa header eksplisit.',
            ];
        }

        $nonEmpty = 0;
        $textLabels = 0;

        for (
            $column = 1;
            $column <= $maxColumn;
            $column++
        ) {
            $letter =
                Coordinate::stringFromColumnIndex(
                    $column
                );

            $raw =
                $worksheet
                    ->getCell(
                        $letter
                        . $headerRow
                    )
                    ->getValue();

            if ($raw instanceof RichText) {
                $raw =
                    $raw->getPlainText();
            }

            if (
                $raw === null
                || (
                    is_string($raw)
                    && trim(
                        $raw
                    ) === ''
                )
            ) {
                continue;
            }

            $nonEmpty++;

            if (
                is_string($raw)
                && ! is_numeric(
                    trim(
                        $raw
                    )
                )
            ) {
                $textLabels++;
            }
        }

        $plausible =
            $nonEmpty >= 2
            && $textLabels >= 2;

        return [
            'plausible' =>
                $plausible,

            'message' =>
                $plausible
                    ? 'Header memiliki minimal dua label teks.'
                    : 'Header tersimpan perlu dicek manual karena label teks sangat sedikit.',
        ];
    }


    /**
     * Baca source spreadsheet tanpa memanggil SpreadsheetAnalyticsService.
     * Audit menghitung ulang fakta source secara terpisah.
     */
    private function loadSpreadsheet(
        string $absolutePath,
        string $extension
    ) {

        if ($extension === 'csv') {

            $reader =
                new Csv();

            $reader->setDelimiter(
                $this->detectCsvDelimiter(
                    $absolutePath
                )
            );

            $reader->setEnclosure(
                '"'
            );

            $reader->setEscapeCharacter(
                '\\'
            );

            $reader->setSheetIndex(
                0
            );

            return $reader->load(
                $absolutePath
            );
        }

        $reader =
            \PhpOffice\PhpSpreadsheet\IOFactory
                ::createReaderForFile(
                    $absolutePath
                );

        $reader->setReadDataOnly(
            false
        );

        if (
            method_exists(
                $reader,
                'setReadEmptyCells'
            )
        ) {
            $reader->setReadEmptyCells(
                false
            );
        }

        return $reader->load(
            $absolutePath
        );
    }


    private function buildHeaders(
        Worksheet $worksheet,
        int $headerRow,
        int $maxColumn
    ): array {

        $headers = [];
        $usedNames = [];

        for (
            $column = 1;
            $column <= $maxColumn;
            $column++
        ) {
            $letter =
                Coordinate::stringFromColumnIndex(
                    $column
                );

            $raw = null;

            if ($headerRow > 0) {
                $raw =
                    $worksheet
                        ->getCell(
                            $letter
                            . $headerRow
                        )
                        ->getValue();

                if ($raw instanceof RichText) {
                    $raw =
                        $raw->getPlainText();
                }
            }

            $name =
                is_scalar($raw)
                    ? trim(
                        (string) $raw
                    )
                    : '';

            if ($name === '') {
                $name =
                    'Kolom '
                    . $letter;
            }

            $baseName =
                $name;

            $counter = 2;

            while (
                isset(
                    $usedNames[
                        mb_strtolower(
                            $name
                        )
                    ]
                )
            ) {
                $name =
                    $baseName
                    . ' ('
                    . $counter
                    . ')';

                $counter++;
            }

            $usedNames[
                mb_strtolower(
                    $name
                )
            ] = true;

            $headers[] = [
                'index' =>
                    $column,

                'letter' =>
                    $letter,

                'name' =>
                    $name,
            ];
        }

        return $headers;
    }


    private function extractCellValue(
        $cell
    ): array {

        $raw =
            $cell->getValue();

        if ($raw instanceof RichText) {
            $raw =
                $raw->getPlainText();
        }

        if (
            $raw === null
            || (
                is_string($raw)
                && trim(
                    $raw
                ) === ''
            )
        ) {
            return [
                'type' =>
                    'blank',

                'value' =>
                    null,
            ];
        }

        if (
            $cell->getDataType()
            === DataType::TYPE_FORMULA
        ) {
            try {

                $raw =
                    $cell
                        ->getCalculatedValue();

            } catch (Throwable) {

                $raw =
                    $cell
                        ->getValue();
            }
        }

        if ($raw instanceof RichText) {
            $raw =
                $raw->getPlainText();
        }

        if (
            is_numeric($raw)
            && ExcelDate::isDateTime(
                $cell
            )
        ) {
            try {

                $date =
                    ExcelDate
                        ::excelToDateTimeObject(
                            (float) $raw
                        );

                return [
                    'type' =>
                        'date',

                    'value' =>
                        $date->format(
                            'Y-m-d H:i:s'
                        ),
                ];

            } catch (Throwable) {
                /*
                | Bila style tanggal source rusak,
                | lanjut ke numeric.
                */
            }
        }

        if (is_bool($raw)) {
            return [
                'type' =>
                    'boolean',

                'value' =>
                    $raw,
            ];
        }

        if (
            is_int($raw)
            || is_float($raw)
            || (
                is_string($raw)
                && is_numeric(
                    trim(
                        $raw
                    )
                )
            )
        ) {
            $numeric =
                (float) $raw;

            if (
                floor(
                    $numeric
                ) === $numeric
                && abs(
                    $numeric
                ) <= PHP_INT_MAX
            ) {
                $numeric =
                    (int) $numeric;
            }

            return [
                'type' =>
                    'numeric',

                'value' =>
                    $numeric,
            ];
        }

        if (is_string($raw)) {

            $date =
                $this
                    ->parseStrictDateString(
                        trim(
                            $raw
                        )
                    );

            if ($date !== null) {
                return [
                    'type' =>
                        'date',

                    'value' =>
                        $date,
                ];
            }

            return [
                'type' =>
                    'text',

                'value' =>
                    trim(
                        $raw
                    ),
            ];
        }

        return [
            'type' =>
                'text',

            'value' =>
                is_scalar($raw)
                    ? (string) $raw
                    : json_encode(
                        $raw,
                        JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                    ),
        ];
    }


    private function initializeStatistics(
        array $headers
    ): array {

        return array_map(
            fn (array $header) => [
                'header' =>
                    $header[
                        'name'
                    ],

                'letter' =>
                    $header[
                        'letter'
                    ],

                'type' =>
                    'empty',

                'non_empty_count' =>
                    0,

                'blank_count' =>
                    0,

                'numeric_count' =>
                    0,

                'date_count' =>
                    0,

                'text_count' =>
                    0,

                'boolean_count' =>
                    0,

                'unique_values' =>
                    [],

                'unique_count' =>
                    0,

                'unique_count_truncated' =>
                    false,

                'frequencies' =>
                    [],

                'numeric' => [
                    'sum' =>
                        0.0,

                    'min' =>
                        null,

                    'max' =>
                        null,

                    'average' =>
                        null,
                ],

                'date' => [
                    'min' =>
                        null,

                    'max' =>
                        null,
                ],
            ],
            $headers
        );
    }


    private function accumulateStatistics(
        array &$statistics,
        array $typed
    ): void {

        $type =
            $typed[
                'type'
            ];

        $value =
            $typed[
                'value'
            ];

        if ($type === 'blank') {

            $statistics[
                'blank_count'
            ]++;

            return;
        }

        $statistics[
            'non_empty_count'
        ]++;

        $counterKey =
            match ($type) {

                'numeric' =>
                    'numeric_count',

                'date' =>
                    'date_count',

                'boolean' =>
                    'boolean_count',

                default =>
                    'text_count',
            };

        $statistics[
            $counterKey
        ]++;

        $frequencyKey =
            $this->valueKey(
                $value
            );

        $distinctLimit =
            max(
                100,
                (int) config(
                    'smartfind_analytics.distinct_tracking_limit',
                    5000
                )
            );

        if (
            ! isset(
                $statistics[
                    'unique_values'
                ][
                    $frequencyKey
                ]
            )
            && count(
                $statistics[
                    'unique_values'
                ]
            ) < $distinctLimit
        ) {
            $statistics[
                'unique_values'
            ][
                $frequencyKey
            ] = true;

        } elseif (
            ! isset(
                $statistics[
                    'unique_values'
                ][
                    $frequencyKey
                ]
            )
        ) {
            $statistics[
                'unique_count_truncated'
            ] = true;
        }

        if (
            ! $statistics[
                'unique_count_truncated'
            ]
        ) {
            if (
                ! isset(
                    $statistics[
                        'frequencies'
                    ][
                        $frequencyKey
                    ]
                )
            ) {
                $statistics[
                    'frequencies'
                ][
                    $frequencyKey
                ] = [
                    'value' =>
                        $value,

                    'count' =>
                        0,
                ];
            }

            $statistics[
                'frequencies'
            ][
                $frequencyKey
            ][
                'count'
            ]++;
        }

        if ($type === 'numeric') {

            $number =
                (float) $value;

            $statistics[
                'numeric'
            ][
                'sum'
            ] +=
                $number;

            $statistics[
                'numeric'
            ][
                'min'
            ] =
                $statistics[
                    'numeric'
                ][
                    'min'
                ] === null
                    ? $number
                    : min(
                        $statistics[
                            'numeric'
                        ][
                            'min'
                        ],
                        $number
                    );

            $statistics[
                'numeric'
            ][
                'max'
            ] =
                $statistics[
                    'numeric'
                ][
                    'max'
                ] === null
                    ? $number
                    : max(
                        $statistics[
                            'numeric'
                        ][
                            'max'
                        ],
                        $number
                    );
        }

        if ($type === 'date') {

            $date =
                (string) $value;

            if (
                $statistics[
                    'date'
                ][
                    'min'
                ] === null
                || $date
                    < $statistics[
                        'date'
                    ][
                        'min'
                    ]
            ) {
                $statistics[
                    'date'
                ][
                    'min'
                ] = $date;
            }

            if (
                $statistics[
                    'date'
                ][
                    'max'
                ] === null
                || $date
                    > $statistics[
                        'date'
                    ][
                        'max'
                    ]
            ) {
                $statistics[
                    'date'
                ][
                    'max'
                ] = $date;
            }
        }
    }


    private function finalizeStatistics(
        array $statistics
    ): array {

        $topValuesLimit =
            max(
                1,
                (int) config(
                    'smartfind_analytics.top_values_limit',
                    10
                )
            );

        foreach (
            $statistics
            as &$column
        ) {
            $column[
                'unique_count'
            ] =
                count(
                    $column[
                        'unique_values'
                    ]
                );

            $column[
                'type'
            ] =
                $this->classifyColumn(
                    $column
                );

            if (
                $column[
                    'numeric_count'
                ] > 0
            ) {
                $column[
                    'numeric'
                ][
                    'average'
                ] =
                    $column[
                        'numeric'
                    ][
                        'sum'
                    ]
                    / $column[
                        'numeric_count'
                    ];

                $column[
                    'numeric'
                ][
                    'sum'
                ] =
                    round(
                        $column[
                            'numeric'
                        ][
                            'sum'
                        ],
                        6
                    );

                $column[
                    'numeric'
                ][
                    'average'
                ] =
                    round(
                        $column[
                            'numeric'
                        ][
                            'average'
                        ],
                        6
                    );

            } else {

                $column[
                    'numeric'
                ] = null;
            }

            if (
                $column[
                    'date_count'
                ] === 0
            ) {
                $column[
                    'date'
                ] = null;
            }

            $frequencies =
                array_values(
                    $column[
                        'frequencies'
                    ]
                );

            usort(
                $frequencies,
                fn (
                    array $left,
                    array $right
                ) =>
                    $right[
                        'count'
                    ]
                    <=>
                    $left[
                        'count'
                    ]
            );

            $column[
                'top_values'
            ] =
                array_slice(
                    $frequencies,
                    0,
                    $topValuesLimit
                );

            unset(
                $column[
                    'unique_values'
                ],
                $column[
                    'frequencies'
                ]
            );
        }

        unset($column);

        return array_values(
            $statistics
        );
    }


    private function classifyColumn(
        array $statistics
    ): string {

        $nonEmpty =
            (int) (
                $statistics[
                    'non_empty_count'
                ]
                ?? 0
            );

        if ($nonEmpty === 0) {
            return 'empty';
        }

        $numeric =
            (int) (
                $statistics[
                    'numeric_count'
                ]
                ?? 0
            );

        $date =
            (int) (
                $statistics[
                    'date_count'
                ]
                ?? 0
            );

        $boolean =
            (int) (
                $statistics[
                    'boolean_count'
                ]
                ?? 0
            );

        $text =
            (int) (
                $statistics[
                    'text_count'
                ]
                ?? 0
            );

        if (
            $date
            / $nonEmpty
            >= 0.70
        ) {
            return 'date';
        }

        if (
            $numeric
            / $nonEmpty
            >= 0.80
        ) {
            return 'numeric';
        }

        if (
            $boolean
            / $nonEmpty
            >= 0.90
        ) {
            return 'boolean';
        }

        $uniqueCount =
            (int) (
                $statistics[
                    'unique_count'
                ]
                ?? 0
            );

        $uniqueTruncated =
            (bool) (
                $statistics[
                    'unique_count_truncated'
                ]
                ?? false
            );

        if (
            ! $uniqueTruncated
            && $text > 0
            && $uniqueCount >= 1
            && $uniqueCount <= 50
            && $uniqueCount
                <= max(
                    10,
                    (int) ceil(
                        $nonEmpty * 0.35
                    )
                )
        ) {
            return 'categorical';
        }

        if (
            $text
            / $nonEmpty
            >= 0.70
        ) {
            return 'text';
        }

        return 'mixed';
    }


    private function compareNumericSummary(
        array &$checks,
        string $scope,
        ?array $expected,
        ?array $actual
    ): void {

        if (
            $expected === null
            || $actual === null
        ) {
            $this->addCheck(
                $checks,
                scope:
                    $scope,
                name:
                    'Ringkasan numerik',
                expected:
                    $expected,
                actual:
                    $actual,
                severity:
                    'error'
            );

            return;
        }

        foreach (
            [
                'sum',
                'min',
                'max',
                'average',
            ]
            as $field
        ) {
            $expectedValue =
                $expected[
                    $field
                ]
                ?? null;

            $actualValue =
                $actual[
                    $field
                ]
                ?? null;

            $passed =
                (
                    $expectedValue === null
                    && $actualValue === null
                )
                || (
                    is_numeric(
                        $expectedValue
                    )
                    && is_numeric(
                        $actualValue
                    )
                    && $this->numbersNearlyEqual(
                        (float) $expectedValue,
                        (float) $actualValue
                    )
                );

            $this->addCheck(
                $checks,
                scope:
                    $scope,
                name:
                    'numeric.'
                    . $field,
                expected:
                    $expectedValue,
                actual:
                    $actualValue,
                severity:
                    'error',
                explicitPass:
                    $passed
            );
        }
    }


    private function numbersNearlyEqual(
        float $left,
        float $right
    ): bool {

        $difference =
            abs(
                $left
                - $right
            );

        if (
            $difference
            <= 0.000001
        ) {
            return true;
        }

        $scale =
            max(
                abs($left),
                abs($right),
                1.0
            );

        return (
            $difference
            / $scale
        ) <= 0.000001;
    }


    private function addCheck(
        array &$checks,
        string $scope,
        string $name,
        mixed $expected,
        mixed $actual,
        string $severity = 'error',
        ?string $message = null,
        ?bool $explicitPass = null
    ): void {

        $passed =
            $explicitPass
            ?? $this->valuesEqual(
                $expected,
                $actual
            );

        /*
        | severity=info tidak boleh membuat audit gagal.
        */

        $status =
            $severity === 'info'
                ? 'INFO'
                : (
                    $passed
                        ? 'PASS'
                        : (
                            $severity === 'warning'
                                ? 'WARN'
                                : 'FAIL'
                        )
                );

        $checks[] = [
            'scope' =>
                $scope,

            'name' =>
                $name,

            'status' =>
                $status,

            'severity' =>
                $severity,

            'expected' =>
                $expected,

            'actual' =>
                $actual,

            'message' =>
                $message,
        ];
    }


    private function valuesEqual(
        mixed $expected,
        mixed $actual
    ): bool {

        if (
            is_float($expected)
            || is_float($actual)
        ) {
            if (
                is_numeric($expected)
                && is_numeric($actual)
            ) {
                return $this->numbersNearlyEqual(
                    (float) $expected,
                    (float) $actual
                );
            }
        }

        return $expected === $actual;
    }


    private function finalizeReport(
        DocumentVersion $version,
        array $checks,
        array $sheetReports,
        ?string $sourceHash
    ): array {

        $failed =
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

        $passed =
            collect(
                $checks
            )
                ->where(
                    'status',
                    'PASS'
                )
                ->count();

        $info =
            collect(
                $checks
            )
                ->where(
                    'status',
                    'INFO'
                )
                ->count();

        $scoredChecks =
            $passed
            + $failed;

        $score =
            $scoredChecks > 0
                ? round(
                    (
                        $passed
                        / $scoredChecks
                    ) * 100,
                    2
                )
                : 0.0;

        return [
            'passed' =>
                $failed === 0,

            'status' =>
                $failed === 0
                    ? 'PASS'
                    : 'FAIL',

            'score' =>
                $score,

            'document_version' => [
                'id' =>
                    (int) $version->id,

                'document_id' =>
                    (int) $version->document_id,

                'revision_number' =>
                    (int) $version->revision_number,

                'file_name' =>
                    $version->file_name,

                'file_extension' =>
                    strtolower(
                        (string) $version->file_extension
                    ),
            ],

            'source_sha256' =>
                $sourceHash,

            'summary' => [
                'passed' =>
                    $passed,

                'failed' =>
                    $failed,

                'warnings' =>
                    $warnings,

                'info' =>
                    $info,

                'total_checks' =>
                    count(
                        $checks
                    ),
            ],

            'checks' =>
                $checks,

            'sheets' =>
                $sheetReports,

            'audited_at' =>
                now()
                    ->toIso8601String(),
        ];
    }


    private function detectCsvDelimiter(
        string $absolutePath
    ): string {

        $handle =
            fopen(
                $absolutePath,
                'rb'
            );

        if ($handle === false) {
            return ',';
        }

        $sample =
            fread(
                $handle,
                8192
            );

        fclose($handle);

        if (
            $sample === false
            || $sample === ''
        ) {
            return ',';
        }

        $candidates = [
            ',' =>
                substr_count(
                    $sample,
                    ','
                ),

            ';' =>
                substr_count(
                    $sample,
                    ';'
                ),

            "\t" =>
                substr_count(
                    $sample,
                    "\t"
                ),

            '|' =>
                substr_count(
                    $sample,
                    '|'
                ),
        ];

        arsort(
            $candidates
        );

        $delimiter =
            array_key_first(
                $candidates
            );

        return (
            $delimiter !== null
            && $candidates[
                $delimiter
            ] > 0
        )
            ? $delimiter
            : ',';
    }


    private function parseStrictDateString(
        string $value
    ): ?string {

        if ($value === '') {
            return null;
        }

        $formats = [
            'Y-m-d',
            'Y/m/d',
            'd/m/Y',
            'd-m-Y',
            'd.m.Y',
            'Y-m-d H:i:s',
            'd/m/Y H:i:s',
            'd-m-Y H:i:s',
        ];

        foreach (
            $formats
            as $format
        ) {
            $date =
                \DateTimeImmutable
                    ::createFromFormat(
                        '!'
                        . $format,
                        $value
                    );

            if ($date === false) {
                continue;
            }

            $errors =
                \DateTimeImmutable
                    ::getLastErrors();

            if (
                $errors !== false
                && (
                    $errors[
                        'warning_count'
                    ] > 0
                    || $errors[
                        'error_count'
                    ] > 0
                )
            ) {
                continue;
            }

            if (
                $date->format(
                    $format
                ) !== $value
            ) {
                continue;
            }

            return str_contains(
                $format,
                'H'
            )
                ? $date->format(
                    'Y-m-d H:i:s'
                )
                : $date->format(
                    'Y-m-d'
                );
        }

        return null;
    }


    private function valueKey(
        mixed $value
    ): string {

        if (is_bool($value)) {
            return $value
                ? 'bool:1'
                : 'bool:0';
        }

        if (
            is_int($value)
            || is_float($value)
        ) {
            return 'num:'
                . (string) $value;
        }

        return 'str:'
            . mb_strtolower(
                trim(
                    (string) $value
                )
            );
    }
}
