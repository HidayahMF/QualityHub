<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_scan_metadata', function (Blueprint $table) {
            $table->id();

            // Satu metadata scan hanya dimiliki oleh satu dokumen.
            $table->foreignId('document_id')
                ->unique()
                ->constrained('documents')
                ->cascadeOnDelete();

            // Sumber dokumen: scan dari kamera, upload foto, atau sumber lain.
            $table->string('source_type', 30)->default('scan');

            // Jenis proses scan, misalnya retooling atau general.
            $table->string('scan_type', 50)->nullable();

            // Metadata khusus berkas QC hasil scan.
            $table->date('scan_date')->nullable();
            $table->string('shift', 20)->nullable();
            $table->string('line', 100)->nullable();
            $table->string('from_type', 150)->nullable();
            $table->string('to_type', 150)->nullable();
            $table->time('scan_time')->nullable();

            // Informasi proses capture/file gambar.
            $table->string('image_format', 20)->nullable();
            $table->string('capture_source', 30)->nullable();

            $table->timestamps();

            $table->index('source_type');
            $table->index('scan_type');
            $table->index('scan_date');
            $table->index('shift');
            $table->index('line');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_scan_metadata');
    }
};
