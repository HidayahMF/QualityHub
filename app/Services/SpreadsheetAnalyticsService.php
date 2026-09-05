<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentAnalytics;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use Throwable;

class SpreadsheetAnalyticsService
{
    public function __construct(
        private readonly SpreadsheetDetectorService $detector,
        private readonly ChartSuggestionService $chartSuggestion,
        private readonly PrivateDocumentStorageService $privateStorage
    ) {
    }


    /**
     * Memproses satu document version menjadi analytics.
     */
    public function analyze(
        DocumentVersion $version,
        bool $force = false
    ): DocumentAnalytics {

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
                'Format file .'
                . $extension
                . ' tidak didukung oleh Spreadsheet Analytics.'
            );
        }

        $document =
            Document::query()
                ->findOrFail(
                    $version->document_id
                );

        $analytics =
            DocumentAnalytics::query()
                ->firstOrNew([
                    'document_version_id' =>
                        $version->id,
                ]);

        if (
            ! $force
            && $analytics->exists
            && $analytics->status === 'ready'
        ) {
            return $analytics
                ->load('sheets');
        }

        $analytics->fill([
            'document_id' =>
                $document->id,

            'revision_number' =>
                (int) $version->revision_number,

            'status' =>
                'processing',

            'processed_at' =>
                null,

            'error_message' =>
                null,
        ]);

        $analytics->save();

        try {

            $diskName =
                (string) config(
                    'smartfind_analytics.disk',
                    'bmc_documents'
                );

            $filePath =
                (string) $version->file_path;

            $absolutePath =
                $this->privateStorage
                    ->resolveExistingFile(
                        $filePath,
                        $diskName
                    );

            if ($absolutePath === null) {
                throw new RuntimeException(
                    'File spreadsheet tidak ditemukan atau path private storage tidak aman.'
                );
            }

            $this->assertFileWithinLimits(
                $absolutePath
            );

            $spreadsheet =
                $this->loadSpreadsheet(
                    $absolutePath,
                    $extension
                );

            $sheetResults = [];

            $workbookTotals = [
                'sheet_count' =>
                    $spreadsheet
                        ->getSheetCount(),

                'total_rows' =>
                    0,

                'total_columns' =>
                    0,

                'numeric_cells' =>
                    0,

                'blank_cells' =>
                    0,
            ];

            $schemaSheets = [];

            foreach (
                $spreadsheet
                    ->getWorksheetIterator()
                as $sheetIndex => $sheet
            ) {
                $sheetResult =
                    $this->analyzeSheet(
                        $sheet,
                        (int) $sheetIndex
                    );

                $sheetResults[] =
                    $sheetResult;

                $workbookTotals[
                    'total_rows'
                ] +=
                    $sheetResult[
                        'row_count'
                    ];

                $workbookTotals[
                    'total_columns'
                ] +=
                    $sheetResult[
                        'column_count'
                    ];

                $workbookTotals[
                    'numeric_cells'
                ] +=
                    $sheetResult[
                        'numeric_cells'
                    ];

                $workbookTotals[
                    'blank_cells'
                ] +=
                    $sheetResult[
                        'blank_cells'
                    ];

                $schemaSheets[] = [
                    'sheet_name' =>
                        $sheetResult[
                            'sheet_name'
                        ],

                    'sheet_index' =>
                        $sheetResult[
                            'sheet_index'
                        ],

                    'header_row' =>
                        $sheetResult[
                            'header_row'
                        ],

                    'row_count' =>
                        $sheetResult[
                            'row_count'
                        ],

                    'column_count' =>
                        $sheetResult[
                            'column_count'
                        ],

                    'columns' =>
                        array_map(
                            fn (array $column) => [
                                'header' =>
                                    $column[
                                        'header'
                                    ],

                                'letter' =>
                                    $column[
                                        'letter'
                                    ],

                                'type' =>
                                    $column[
                                        'type'
                                    ],
                            ],
                            $sheetResult[
                                'statistics'
                            ]
                        ),
                ];
            }

            /*
            | Setelah parsing selesai, objek workbook dilepas secepat mungkin.
            */

            $spreadsheet
                ->disconnectWorksheets();

            unset($spreadsheet);


            $summary = [
                'parser' =>
                    'PhpSpreadsheet',

                'parser_version' =>
                    '16C-1',

                'file_name' =>
                    $version->file_name,

                'file_extension' =>
                    $extension,

                'file_size' =>
                    (int) $version->file_size,

                'document_id' =>
                    $document->id,

                'document_title' =>
                    $document->title,

                'revision_number' =>
                    (int) $version->revision_number,

                'sheet_names' =>
                    array_column(
                        $sheetResults,
                        'sheet_name'
                    ),

                'preview_rows_per_sheet' =>
                    (int) config(
                        'smartfind_analytics.preview_rows',
                        20
                    ),

                'generated_at' =>
                    now()->toIso8601String(),
            ];


            DB::transaction(
                function () use (
                    $analytics,
                    $workbookTotals,
                    $summary,
                    $schemaSheets,
                    $sheetResults
                ) {

                    /*
                    | Reprocessing mengganti hasil analytics,
                    | bukan menambah duplikat sheet.
                    */

                    $analytics
                        ->sheets()
                        ->delete();

                    $analytics->update([
                        'status' =>
                            'ready',

                        'sheet_count' =>
                            $workbookTotals[
                                'sheet_count'
                            ],

                        'total_rows' =>
                            $workbookTotals[
                                'total_rows'
                            ],

                        'total_columns' =>
                            $workbookTotals[
                                'total_columns'
                            ],

                        'numeric_cells' =>
                            $workbookTotals[
                                'numeric_cells'
                            ],

                        'blank_cells' =>
                            $workbookTotals[
                                'blank_cells'
                            ],

                        'summary' =>
                            $summary,

                        'schema' => [
                            'sheets' =>
                                $schemaSheets,
                        ],

                        'processed_at' =>
                            now(),

                        'error_message' =>
                            null,
                    ]);

                    foreach (
                        $sheetResults
                        as $sheetResult
                    ) {
                        $analytics
                            ->sheets()
                            ->create([
                                'sheet_name' =>
                                    $sheetResult[
                                        'sheet_name'
                                    ],

                                'sheet_index' =>
                                    $sheetResult[
                                        'sheet_index'
                                    ],

                                'row_count' =>
                                    $sheetResult[
                                        'row_count'
                                    ],

                                'column_count' =>
                                    $sheetResult[
                                        'column_count'
                                    ],

                                'headers' =>
                                    $sheetResult[
                                        'headers'
                                    ],

                                'preview_data' =>
                                    $sheetResult[
                                        'preview_data'
                                    ],

                                'statistics' =>
                                    $sheetResult[
                                        'statistics'
                                    ],

                                'chart_suggestions' =>
                                    $sheetResult[
                                        'chart_suggestions'
                                    ],
                            ]);
                    }
                }
            );

            return $analytics
                ->fresh()
                ->load('sheets');

        } catch (Throwable $exception) {

            $analytics->update([
                'status' =>
                    'failed',

                'processed_at' =>
                    null,

                'error_message' =>
                    Str::limit(
                        $exception->getMessage(),
                        60000,
                        ''
                    ),
            ]);

            throw $exception;
        }
    }


    /**
     * Memproses satu worksheet.
     */
    private function analyzeSheet(
        Worksheet $sheet,
        int $sheetIndex
    ): array {

        $highestDataRow =
            max(
                1,
                (int) $sheet
                    ->getHighestDataRow()
            );

        $highestDataColumn =
            $sheet
                ->getHighestDataColumn();

        $maxColumn =
            max(
                1,
                Coordinate::columnIndexFromString(
                    $highestDataColumn
                )
            );

        $maxRowsPerSheet =
            max(
                1,
                (int) config(
                    'smartfind_analytics.max_rows_per_sheet',
                    100000
                )
            );

        $maxColumnsPerSheet =
            max(
                1,
                (int) config(
                    'smartfind_analytics.max_columns_per_sheet',
                    200
                )
            );

        if (
            $highestDataRow
            > $maxRowsPerSheet
        ) {
            throw new RuntimeException(
                'Sheet "'
                . $sheet->getTitle()
                . '" memiliki '
                . number_format(
                    $highestDataRow,
                    0,
                    ',',
                    '.'
                )
                . ' baris dan melewati batas aman '
                . number_format(
                    $maxRowsPerSheet,
                    0,
                    ',',
                    '.'
                )
                . ' baris. Batas dapat diatur melalui SMARTFIND_ANALYTICS_MAX_ROWS_PER_SHEET.'
            );
        }

        if (
            $maxColumn
            > $maxColumnsPerSheet
        ) {
            throw new RuntimeException(
                'Sheet "'
                . $sheet->getTitle()
                . '" memiliki '
                . $maxColumn
                . ' kolom dan melewati batas aman '
                . $maxColumnsPerSheet
                . ' kolom.'
            );
        }

        $headerRow =
            $this->detector
                ->detectHeaderRow(
                    $sheet,
                    $maxColumn,
                    $highestDataRow,
                    (int) config(
                        'smartfind_analytics.header_scan_rows',
                        25
                    )
                );

        $headers =
            $this->detector
                ->buildHeaders(
                    $sheet,
                    $headerRow,
                    $maxColumn
                );

        $statistics =
            $this->initializeStatistics(
                $headers
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
            $rowValues = [];
            $rowHasData = false;
            $typedCells = [];

            foreach (
                $headers
                as $columnOffset => $header
            ) {
                $coordinate =
                    $header['letter']
                    . $row;

                $cell =
                    $sheet->getCell(
                        $coordinate
                    );

                $typed =
                    $this->extractCellValue(
                        $cell
                    );

                $typedCells[
                    $columnOffset
                ] = $typed;

                if (
                    $typed['type']
                    !== 'blank'
                ) {
                    $rowHasData = true;
                }
            }

            /*
            | Baris kosong sepenuhnya tidak dihitung sebagai data row.
            */

            if (! $rowHasData) {
                continue;
            }

            $rowCount++;

            foreach (
                $headers
                as $columnOffset => $header
            ) {
                $typed =
                    $typedCells[
                        $columnOffset
                    ];

                $name =
                    $header['name'];

                $rowValues[
                    $name
                ] =
                    $typed['value'];

                $this->accumulateStatistics(
                    $statistics[
                        $columnOffset
                    ],
                    $typed
                );

                if (
                    $typed['type']
                    === 'numeric'
                ) {
                    $numericCells++;
                }

                if (
                    $typed['type']
                    === 'blank'
                ) {
                    $blankCells++;
                }
            }

            if (
                count($previewData)
                < $previewLimit
            ) {
                $previewData[] =
                    $rowValues;
            }
        }

        $statistics =
            $this->finalizeStatistics(
                $statistics
            );

        $chartSuggestions =
            $this->chartSuggestion
                ->suggest(
                    $sheet->getTitle(),
                    $statistics
                );

        return [
            'sheet_name' =>
                $sheet->getTitle(),

            'sheet_index' =>
                $sheetIndex,

            'header_row' =>
                $headerRow,

            'row_count' =>
                $rowCount,

            'column_count' =>
                count($headers),

            'numeric_cells' =>
                $numericCells,

            'blank_cells' =>
                $blankCells,

            'headers' =>
                array_map(
                    fn (array $header) =>
                        $header['name'],
                    $headers
                ),

            'preview_data' =>
                $previewData,

            'statistics' =>
                $statistics,

            'chart_suggestions' =>
                $chartSuggestions,
        ];
    }


    /**
     * Membaca file spreadsheet dengan reader sesuai format.
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

            /*
            | Data CSV tidak memiliki style cell Excel.
            */

            return $reader->load(
                $absolutePath
            );
        }

        $reader =
            \PhpOffice\PhpSpreadsheet\IOFactory
                ::createReaderForFile(
                    $absolutePath
                );

        /*
        | Style tetap dibaca agar tanggal Excel dapat dideteksi secara akurat.
        */

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


    /**
     * Normalisasi cell menjadi nilai JSON-safe + tipe.
     */
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
                && trim($raw) === ''
            )
        ) {
            return [
                'type' =>
                    'blank',

                'value' =>
                    null,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Formula
        |--------------------------------------------------------------------------
        */

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
                    $cell->getValue();
            }
        }

        if ($raw instanceof RichText) {
            $raw =
                $raw->getPlainText();
        }


        /*
        |--------------------------------------------------------------------------
        | Excel Date
        |--------------------------------------------------------------------------
        */

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
                | Jika style Excel tidak valid, lanjut sebagai numeric.
                */
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Boolean
        |--------------------------------------------------------------------------
        */

        if (is_bool($raw)) {
            return [
                'type' =>
                    'boolean',

                'value' =>
                    $raw,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Numeric
        |--------------------------------------------------------------------------
        */

        if (
            is_int($raw)
            || is_float($raw)
            || (
                is_string($raw)
                && is_numeric(
                    trim($raw)
                )
            )
        ) {
            $numeric =
                (float) $raw;

            if (
                floor($numeric)
                === $numeric
                && abs($numeric)
                    <= PHP_INT_MAX
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


        /*
        |--------------------------------------------------------------------------
        | Strict Date String
        |--------------------------------------------------------------------------
        |
        | Format sengaja dibatasi supaya part number / kode tidak salah
        | dianggap sebagai tanggal.
        |
        */

        if (is_string($raw)) {

            $date =
                $this
                    ->parseStrictDateString(
                        trim($raw)
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
                    trim($raw),
            ];
        }


        /*
        | Nilai kompleks dibuat string agar aman masuk JSON.
        */

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
                    $header['name'],

                'letter' =>
                    $header['letter'],

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
            $typed['type'];

        $value =
            $typed['value'];

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


        /*
        |--------------------------------------------------------------------------
        | Distinct & Frequency
        |--------------------------------------------------------------------------
        */

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

        /*
        | Frequency hanya perlu dipertahankan selama distinct belum terlalu besar.
        */

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


        /*
        |--------------------------------------------------------------------------
        | Numeric Statistics
        |--------------------------------------------------------------------------
        */

        if ($type === 'numeric') {

            $number =
                (float) $value;

            $statistics[
                'numeric'
            ][
                'sum'
            ] += $number;

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


        /*
        |--------------------------------------------------------------------------
        | Date Statistics
        |--------------------------------------------------------------------------
        */

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

            if (
                $column[
                    'unique_count_truncated'
                ]
            ) {
                /*
                | Nilai ini merupakan lower bound, bukan estimasi.
                */

                $column[
                    'unique_count'
                ] =
                    count(
                        $column[
                            'unique_values'
                        ]
                    );
            }

            $column[
                'type'
            ] =
                $this->detector
                    ->classifyColumn(
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
                $column['numeric'] =
                    null;
            }

            if (
                $column[
                    'date_count'
                ] === 0
            ) {
                $column['date'] =
                    null;
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
                    $right['count']
                    <=> $left['count']
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


    private function assertFileWithinLimits(
        string $absolutePath
    ): void {

        if (! is_file($absolutePath)) {
            throw new RuntimeException(
                'Path spreadsheet tidak valid.'
            );
        }

        $size =
            filesize(
                $absolutePath
            );

        if ($size === false) {
            throw new RuntimeException(
                'Ukuran file spreadsheet tidak dapat dibaca.'
            );
        }

        $maxFileSizeMb =
            max(
                1,
                (int) config(
                    'smartfind_analytics.max_file_size_mb',
                    50
                )
            );

        $maxBytes =
            $maxFileSizeMb
            * 1024
            * 1024;

        if ($size > $maxBytes) {
            throw new RuntimeException(
                'Ukuran file spreadsheet '
                . round(
                    $size / 1024 / 1024,
                    2
                )
                . ' MB melewati batas parser '
                . $maxFileSizeMb
                . ' MB. Batas dapat diatur melalui SMARTFIND_ANALYTICS_MAX_FILE_SIZE_MB.'
            );
        }
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

        arsort($candidates);

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


    /**
     * Date parsing yang sengaja konservatif.
     */
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

        foreach ($formats as $format) {

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
