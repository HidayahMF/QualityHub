<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SpreadsheetDetectorService
{
    /**
     * Mendeteksi baris header secara deterministic.
     *
     * Baris dengan minimal 2 nilai diprioritaskan. Baris yang lebih banyak
     * berisi teks dan memiliki nilai unik mendapat skor lebih tinggi.
     */
    public function detectHeaderRow(
        Worksheet $sheet,
        int $maxColumn,
        int $highestDataRow,
        int $scanRows
    ): int {

        $lastScanRow = min(
            $highestDataRow,
            max(1, $scanRows)
        );

        $bestRow = 0;
        $bestScore = -INF;

        for (
            $row = 1;
            $row <= $lastScanRow;
            $row++
        ) {
            $values = [];

            for (
                $column = 1;
                $column <= $maxColumn;
                $column++
            ) {
                $coordinate =
                    Coordinate::stringFromColumnIndex(
                        $column
                    )
                    . $row;

                $raw =
                    $sheet
                        ->getCell($coordinate)
                        ->getValue();

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
                    continue;
                }

                $values[] = $raw;
            }

            $nonEmpty = count($values);

            /*
            | Title / heading tunggal tidak dianggap header tabel.
            */

            if ($nonEmpty < 2) {
                continue;
            }

            $textCount = 0;
            $numericCount = 0;
            $normalized = [];

            foreach ($values as $value) {

                if (
                    is_string($value)
                    && ! is_numeric(
                        trim($value)
                    )
                ) {
                    $textCount++;
                } elseif (is_numeric($value)) {
                    $numericCount++;
                }

                $normalized[] =
                    mb_strtolower(
                        trim(
                            (string) $value
                        )
                    );
            }

            /*
            | Header yang valid minimal memiliki dua label teks.
            | Jika tidak ada kandidat seperti ini, parser menganggap sheet
            | tidak memiliki header eksplisit dan membuat "Kolom A", dst.
            */

            if ($textCount < 2) {
                continue;
            }

            $uniqueCount =
                count(
                    array_unique(
                        $normalized
                    )
                );

            $score =
                ($nonEmpty * 2)
                + ($textCount * 2)
                + ($uniqueCount * 0.25)
                - ($numericCount * 0.5);

            /*
            | Header biasanya diikuti baris data.
            */

            if ($row < $highestDataRow) {
                $nextNonEmpty =
                    $this->countNonEmptyCells(
                        $sheet,
                        $row + 1,
                        $maxColumn
                    );

                if ($nextNonEmpty >= 2) {
                    $score += 2;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRow = $row;
            }
        }

        return $bestRow;
    }


    /**
     * Membuat nama header unik.
     */
    public function buildHeaders(
        Worksheet $sheet,
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
                    $sheet
                        ->getCell(
                            $letter . $headerRow
                        )
                        ->getValue();

                if ($raw instanceof RichText) {
                    $raw =
                        $raw->getPlainText();
                }
            }

            $name =
                is_scalar($raw)
                    ? trim((string) $raw)
                    : '';

            if ($name === '') {
                $name =
                    'Kolom ' . $letter;
            }

            $baseName = $name;
            $counter = 2;

            while (
                isset(
                    $usedNames[
                        mb_strtolower($name)
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
                mb_strtolower($name)
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


    /**
     * Menentukan tipe semantik sebuah kolom.
     */
    public function classifyColumn(
        array $statistics
    ): string {

        $nonEmpty =
            (int) (
                $statistics['non_empty_count']
                ?? 0
            );

        if ($nonEmpty === 0) {
            return 'empty';
        }

        $numeric =
            (int) (
                $statistics['numeric_count']
                ?? 0
            );

        $date =
            (int) (
                $statistics['date_count']
                ?? 0
            );

        $boolean =
            (int) (
                $statistics['boolean_count']
                ?? 0
            );

        $text =
            (int) (
                $statistics['text_count']
                ?? 0
            );

        if (
            $date / $nonEmpty
            >= 0.70
        ) {
            return 'date';
        }

        if (
            $numeric / $nonEmpty
            >= 0.80
        ) {
            return 'numeric';
        }

        if (
            $boolean / $nonEmpty
            >= 0.90
        ) {
            return 'boolean';
        }

        $uniqueCount =
            (int) (
                $statistics['unique_count']
                ?? 0
            );

        $uniqueTruncated =
            (bool) (
                $statistics[
                    'unique_count_truncated'
                ]
                ?? false
            );

        /*
        | Kolom teks dengan cardinality rendah dianggap kategori.
        */

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

        if ($text / $nonEmpty >= 0.70) {
            return 'text';
        }

        return 'mixed';
    }


    private function countNonEmptyCells(
        Worksheet $sheet,
        int $row,
        int $maxColumn
    ): int {

        $count = 0;

        for (
            $column = 1;
            $column <= $maxColumn;
            $column++
        ) {
            $coordinate =
                Coordinate::stringFromColumnIndex(
                    $column
                )
                . $row;

            $value =
                $sheet
                    ->getCell($coordinate)
                    ->getValue();

            if (
                $value !== null
                && (
                    ! is_string($value)
                    || trim($value) !== ''
                )
            ) {
                $count++;
            }
        }

        return $count;
    }
}
