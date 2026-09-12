<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyimpan metadata produksi secara eksplisit di database.
     *
     * AXLE ASSY:
     * - production_date
     * - axle_assy_type (sudah dibuat migration sebelumnya)
     * - axle_side (sudah dibuat migration sebelumnya)
     *
     * BRAKE ASSY:
     * - production_date
     * - brake_part_prefix
     * - brake_part_variant (sudah dibuat migration sebelumnya)
     * - brake_part_number (gabungan prefix + variant)
     */
    public function up(): void
    {
        Schema::table('document_scan_metadata', function (Blueprint $table) {
            $table->date('production_date')->nullable()->after('scan_date');
            $table->string('brake_part_prefix', 30)->nullable()->after('axle_side');
            $table->string('brake_part_number', 60)->nullable()->after('brake_part_variant');

            $table->index('production_date', 'document_scan_metadata_production_date_idx');
            $table->index('brake_part_number', 'document_scan_metadata_brake_part_number_idx');
        });
    }

    public function down(): void
    {
        Schema::table('document_scan_metadata', function (Blueprint $table) {
            $table->dropIndex('document_scan_metadata_production_date_idx');
            $table->dropIndex('document_scan_metadata_brake_part_number_idx');

            $table->dropColumn([
                'production_date',
                'brake_part_prefix',
                'brake_part_number',
            ]);
        });
    }
};
