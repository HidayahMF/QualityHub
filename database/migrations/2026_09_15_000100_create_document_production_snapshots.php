<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_axle_productions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_id')
                ->unique()
                ->constrained('documents')
                ->cascadeOnDelete();

            $table->foreignId('axle_type_id')
                ->constrained('axle_types')
                ->restrictOnDelete();

            $table->foreignId('axle_position_id')
                ->constrained('axle_positions')
                ->restrictOnDelete();

            $table->foreignId('production_line_id')
                ->nullable()
                ->constrained('production_lines')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('shift')->nullable();
            $table->time('production_time')->nullable();

            $table->string('snapshot_axle_type_code', 50);
            $table->string('snapshot_axle_type_name', 150);
            $table->string('snapshot_position_code', 50);
            $table->string('snapshot_position_name', 150);
            $table->string('snapshot_line_code', 50)->nullable();
            $table->string('snapshot_line_name', 150)->nullable();

            $table->timestamps();
        });

        Schema::create('document_brake_productions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_id')
                ->unique()
                ->constrained('documents')
                ->cascadeOnDelete();

            $table->foreignId('brake_part_number_id')
                ->constrained('brake_part_numbers')
                ->restrictOnDelete();

            $table->foreignId('production_line_id')
                ->nullable()
                ->constrained('production_lines')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('shift')->nullable();
            $table->time('production_time')->nullable();

            $table->string('snapshot_prefix', 30);
            $table->string('snapshot_variant', 20);
            $table->string('snapshot_display_name', 150);
            $table->string('snapshot_line_code', 50)->nullable();
            $table->string('snapshot_line_name', 150)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_brake_productions');
        Schema::dropIfExists('document_axle_productions');
    }
};
