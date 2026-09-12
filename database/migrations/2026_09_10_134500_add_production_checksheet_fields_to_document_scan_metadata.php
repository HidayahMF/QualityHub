<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahan metadata untuk Checksheet Laporan Hasil Produksi:
     * - Axle Assy: Module Assy / Axle Assy + LH / RH
     * - Brake Assy: variant Part No A 4304200
     */
    public function up(): void
    {
        Schema::table('document_scan_metadata', function (Blueprint $table) {
            $table->string('axle_assy_type', 30)->nullable();
            $table->string('axle_side', 10)->nullable();
            $table->string('brake_part_variant', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('document_scan_metadata', function (Blueprint $table) {
            $table->dropColumn([
                'axle_assy_type',
                'axle_side',
                'brake_part_variant',
            ]);
        });
    }
};
