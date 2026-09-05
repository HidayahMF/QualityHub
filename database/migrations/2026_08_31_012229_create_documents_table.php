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
    Schema::create('documents', function (Blueprint $table) {
        $table->id();

        // Kategori dokumen
        $table->foreignId('category_id')
            ->constrained('categories')
            ->restrictOnDelete();

        // User yang mendaftarkan / upload dokumen
        $table->foreignId('uploaded_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        // Informasi utama dokumen
        $table->string('title');
        $table->string('document_code')->nullable();
        $table->string('part_number')->nullable();

        // Informasi file
        $table->string('file_name');
        $table->text('file_path');
        $table->string('file_extension', 20)->nullable();
        $table->string('mime_type')->nullable();
        $table->unsignedBigInteger('file_size')->nullable();

        // Metadata
        $table->date('document_date')->nullable();
        $table->text('description')->nullable();
        $table->text('keywords')->nullable();

        // Status dokumen
        $table->boolean('is_active')->default(true);

        $table->timestamps();

        // Index untuk mempercepat pencarian
        $table->index('title');
        $table->index('document_code');
        $table->index('part_number');
        $table->index('document_date');
        $table->index('is_active');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
