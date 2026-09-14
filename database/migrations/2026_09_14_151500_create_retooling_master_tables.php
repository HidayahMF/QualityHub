<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master data Retooling QC.
     *
     * Struktur:
     * production_lines  -> daftar Line produksi
     * castings          -> daftar Type / Casting
     * line_products     -> Type yang tersedia di setiap Line + Cycle Time
     * document_retoolings -> snapshot Retooling saat dokumen dibuat
     */
    public function up(): void
    {
        Schema::create('production_lines', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();
            $table->string('name', 150);

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->timestamps();
        });


        Schema::create('castings', function (Blueprint $table) {
            $table->id();

            $table->string('code', 80)->unique();
            $table->string('name', 150);

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->timestamps();
        });


        Schema::create('line_products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('production_line_id')
                ->constrained('production_lines')
                ->restrictOnDelete();

            $table->foreignId('casting_id')
                ->constrained('castings')
                ->restrictOnDelete();

            /*
             * Cycle Time disimpan sebagai decimal agar nanti tetap aman
             * jika data asli BMC menggunakan nilai pecahan.
             */
            $table->decimal(
                'cycle_time',
                10,
                2
            );

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->timestamps();

            $table->unique(
                [
                    'production_line_id',
                    'casting_id',
                ],
                'line_products_line_casting_uq'
            );

            $table->index(
                [
                    'production_line_id',
                    'is_active',
                ],
                'line_products_line_active_idx'
            );
        });


        Schema::create('document_retoolings', function (Blueprint $table) {
            $table->id();

            /*
             * Satu dokumen Retooling hanya memiliki satu metadata Retooling.
             */
            $table->foreignId('document_id')
                ->unique()
                ->constrained('documents')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('shift');

            $table->foreignId('production_line_id')
                ->constrained('production_lines')
                ->restrictOnDelete();

            $table->foreignId('from_casting_id')
                ->constrained('castings')
                ->restrictOnDelete();

            $table->foreignId('to_casting_id')
                ->constrained('castings')
                ->restrictOnDelete();

            /*
             * Snapshot Cycle Time.
             *
             * Nilai ini sengaja disalin dari line_products ketika dokumen
             * dibuat supaya dokumen lama tidak berubah jika master Cycle Time
             * di masa depan diperbarui.
             */
            $table->decimal(
                'from_cycle_time',
                10,
                2
            );

            $table->decimal(
                'to_cycle_time',
                10,
                2
            );

            $table->timestamps();

            $table->index(
                [
                    'production_line_id',
                    'shift',
                ],
                'document_retoolings_line_shift_idx'
            );
        });
    }


    /**
     * Rollback dimulai dari tabel paling bergantung.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_retoolings');
        Schema::dropIfExists('line_products');
        Schema::dropIfExists('castings');
        Schema::dropIfExists('production_lines');
    }
};
