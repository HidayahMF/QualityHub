<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan tabel analitik per sheet spreadsheet.
     */
    public function up(): void
    {
        Schema::create('document_analytic_sheets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_analytics_id')
                ->constrained('document_analytics')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Sheet Identity
            |--------------------------------------------------------------------------
            */

            $table->string('sheet_name');

            $table->unsignedInteger('sheet_index');

            /*
            |--------------------------------------------------------------------------
            | Sheet Summary
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('row_count')
                ->default(0);

            $table->unsignedInteger('column_count')
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Parsed Sheet Data
            |--------------------------------------------------------------------------
            |
            | headers:
            | Daftar header/kolom.
            |
            | preview_data:
            | Sampel data untuk preview, bukan seluruh isi spreadsheet.
            |
            | statistics:
            | Statistik hasil parsing kolom.
            |
            | chart_suggestions:
            | Konfigurasi chart deterministic yang disiapkan parser.
            |
            */

            $table->json('headers')
                ->nullable();

            $table->json('preview_data')
                ->nullable();

            $table->json('statistics')
                ->nullable();

            $table->json('chart_suggestions')
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Integrity & Query Performance
            |--------------------------------------------------------------------------
            */

            $table->unique(
                [
                    'document_analytics_id',
                    'sheet_index',
                ],
                'document_analytic_sheets_analytics_index_unique'
            );

            $table->index(
                [
                    'document_analytics_id',
                    'sheet_name',
                ],
                'document_analytic_sheets_name_index'
            );
        });
    }

    /**
     * Menghapus tabel analytics per sheet.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_analytic_sheets');
    }
};
