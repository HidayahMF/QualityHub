<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('axle_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(
                ['is_active', 'sort_order'],
                'axle_types_active_sort_idx'
            );
        });

        Schema::create('axle_positions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(
                ['is_active', 'sort_order'],
                'axle_positions_active_sort_idx'
            );
        });

        Schema::create('brake_part_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 30);
            $table->string('variant', 20);
            $table->string('display_name', 150);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(
                ['prefix', 'variant'],
                'brake_part_numbers_prefix_variant_uq'
            );

            $table->index(
                ['is_active', 'sort_order'],
                'brake_part_numbers_active_sort_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brake_part_numbers');
        Schema::dropIfExists('axle_positions');
        Schema::dropIfExists('axle_types');
    }
};
