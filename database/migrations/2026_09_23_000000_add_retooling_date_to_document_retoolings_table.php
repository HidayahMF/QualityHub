<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_retoolings', function (Blueprint $table) {
            $table->date('retooling_date')
                ->nullable()
                ->after('to_cycle_time');
        });
    }

    public function down(): void
    {
        Schema::table('document_retoolings', function (Blueprint $table) {
            $table->dropColumn('retooling_date');
        });
    }
};
