<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MTC/PPIC IDs are not the same foreign keys as the historical local master tables.
        Schema::table('document_retoolings', function (Blueprint $table) {
            $table->unsignedBigInteger('production_line_id')->nullable()->change();
            $table->unsignedBigInteger('from_casting_id')->nullable()->change();
            $table->unsignedBigInteger('to_casting_id')->nullable()->change();

            $table->string('external_line_id', 50)->nullable();
            $table->string('external_line_code', 150)->nullable();
            $table->string('external_line_name', 255)->nullable();
            $table->string('external_from_casting_code', 80)->nullable();
            $table->string('external_from_casting_name', 255)->nullable();
            $table->string('external_to_casting_code', 80)->nullable();
            $table->string('external_to_casting_name', 255)->nullable();
        });
    }

    public function down(): void
    {
        // Do not silently erase unique QC history if an external snapshot exists.
        if (DB::table('document_retoolings')->whereNotNull('external_line_id')->exists()) {
            throw new \RuntimeException(
                'Rollback ditolak: terdapat snapshot Retooling MTC/PPIC. Gunakan prosedur pemulihan yang telah disetujui.'
            );
        }

        Schema::table('document_retoolings', function (Blueprint $table) {
            $table->dropColumn([
                'external_line_id',
                'external_line_code',
                'external_line_name',
                'external_from_casting_code',
                'external_from_casting_name',
                'external_to_casting_code',
                'external_to_casting_name',
            ]);

            $table->unsignedBigInteger('production_line_id')->nullable(false)->change();
            $table->unsignedBigInteger('from_casting_id')->nullable(false)->change();
            $table->unsignedBigInteger('to_casting_id')->nullable(false)->change();
        });
    }
};
