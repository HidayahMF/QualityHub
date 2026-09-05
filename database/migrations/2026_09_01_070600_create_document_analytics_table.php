<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan tabel ringkasan analitik untuk setiap versi dokumen.
     */
    public function up(): void
    {
        Schema::create('document_analytics', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_id')
                ->constrained('documents')
                ->restrictOnDelete();

            $table->foreignId('document_version_id')
                ->constrained('document_versions')
                ->restrictOnDelete();

            $table->unsignedInteger('revision_number');

            /*
            |--------------------------------------------------------------------------
            | Processing Status
            |--------------------------------------------------------------------------
            |
            | Nilai teknis yang akan digunakan:
            | - pending
            | - processing
            | - ready
            | - failed
            |
            */

            $table->string('status', 30)
                ->default('pending')
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Workbook Summary
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('sheet_count')
                ->default(0);

            $table->unsignedBigInteger('total_rows')
                ->default(0);

            $table->unsignedInteger('total_columns')
                ->default(0);

            $table->unsignedBigInteger('numeric_cells')
                ->default(0);

            $table->unsignedBigInteger('blank_cells')
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Parsed Analytics Data
            |--------------------------------------------------------------------------
            |
            | summary:
            | Ringkasan workbook yang siap digunakan oleh dashboard.
            |
            | schema:
            | Informasi struktur workbook/kolom hasil deteksi parser.
            |
            */

            $table->json('summary')
                ->nullable();

            $table->json('schema')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Processing Metadata
            |--------------------------------------------------------------------------
            */

            $table->timestamp('processed_at')
                ->nullable();

            $table->text('error_message')
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Integrity & Query Performance
            |--------------------------------------------------------------------------
            |
            | Satu document_version hanya boleh memiliki satu analytics record.
            |
            */

            $table->unique(
                'document_version_id',
                'document_analytics_version_unique'
            );

            $table->index(
                [
                    'document_id',
                    'revision_number',
                ],
                'document_analytics_document_revision_index'
            );

            $table->index(
                [
                    'document_id',
                    'status',
                ],
                'document_analytics_document_status_index'
            );

            $table->index(
                [
                    'status',
                    'created_at',
                ],
                'document_analytics_status_created_index'
            );
        });
    }

    /**
     * Menghapus tabel analytics.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_analytics');
    }
};
