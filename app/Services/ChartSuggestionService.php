<?php

namespace App\Services;

class ChartSuggestionService
{
    /**
     * Menghasilkan saran chart deterministic berdasarkan tipe kolom.
     *
     * Tahap 16C hanya menyimpan KONFIGURASI saran chart.
     * Series chart sebenarnya akan dihitung pada tahap analytics dashboard.
     */
    public function suggest(
        string $sheetName,
        array $columnStatistics
    ): array {

        $dateColumns = [];
        $numericColumns = [];
        $categoryColumns = [];

        foreach (
            $columnStatistics
            as $column
        ) {
            $type =
                $column['type']
                ?? 'mixed';

            if ($type === 'date') {
                $dateColumns[] = $column;

                continue;
            }

            if ($type === 'numeric') {
                $numericColumns[] = $column;

                continue;
            }

            if ($type === 'categorical') {
                $categoryColumns[] = $column;
            }
        }

        $suggestions = [];


        /*
        |--------------------------------------------------------------------------
        | Tanggal + Numerik = Line Chart
        |--------------------------------------------------------------------------
        */

        if (
            count($dateColumns) > 0
            && count($numericColumns) > 0
        ) {
            $date =
                $dateColumns[0];

            foreach (
                array_slice(
                    $numericColumns,
                    0,
                    2
                )
                as $numeric
            ) {
                $suggestions[] = [
                    'type' =>
                        'line',

                    'title' =>
                        $numeric['header']
                        . ' berdasarkan '
                        . $date['header'],

                    'label_column' =>
                        $date['header'],

                    'value_column' =>
                        $numeric['header'],

                    'aggregation' =>
                        'sum',

                    'reason' =>
                        'Kolom tanggal dipasangkan dengan kolom numerik.',
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Kategori + Numerik = Bar Chart
        |--------------------------------------------------------------------------
        */

        if (
            count($categoryColumns) > 0
            && count($numericColumns) > 0
            && count($suggestions) < 4
        ) {
            $category =
                $categoryColumns[0];

            $numeric =
                $numericColumns[0];

            $suggestions[] = [
                'type' =>
                    'bar',

                'title' =>
                    $numeric['header']
                    . ' berdasarkan '
                    . $category['header'],

                'label_column' =>
                    $category['header'],

                'value_column' =>
                    $numeric['header'],

                'aggregation' =>
                    'sum',

                'reason' =>
                    'Kolom kategori dipasangkan dengan kolom numerik.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Kategori = Donut Chart berdasarkan Frekuensi
        |--------------------------------------------------------------------------
        */

        foreach (
            $categoryColumns
            as $category
        ) {
            if (count($suggestions) >= 4) {
                break;
            }

            $suggestions[] = [
                'type' =>
                    'donut',

                'title' =>
                    'Distribusi '
                    . $category['header'],

                'label_column' =>
                    $category['header'],

                'value_column' =>
                    null,

                'aggregation' =>
                    'count',

                'reason' =>
                    'Kolom kategori memiliki jumlah nilai unik yang terbatas.',
            ];
        }


        return array_map(
            function (
                array $suggestion
            ) use ($sheetName) {

                $suggestion[
                    'sheet_name'
                ] = $sheetName;

                return $suggestion;
            },
            array_slice(
                $suggestions,
                0,
                4
            )
        );
    }
}
