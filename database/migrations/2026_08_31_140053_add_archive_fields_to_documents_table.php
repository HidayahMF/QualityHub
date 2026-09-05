<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {

            $table->timestamp('archived_at')
                ->nullable()
                ->after('is_active');

            $table->foreignId('archived_by')
                ->nullable()
                ->after('archived_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->text('archive_reason')
                ->nullable()
                ->after('archived_by');

            $table->index([
                'is_active',
                'archived_at',
            ]);
        });
    }


    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {

            $table->dropIndex([
                'is_active',
                'archived_at',
            ]);

            $table->dropConstrainedForeignId(
                'archived_by'
            );

            $table->dropColumn([
                'archived_at',
                'archive_reason',
            ]);
        });
    }
};