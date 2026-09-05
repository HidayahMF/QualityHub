<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Current Revision Pointer
        |--------------------------------------------------------------------------
        */

        if (! Schema::hasColumn('documents', 'current_revision')) {

            Schema::table('documents', function (Blueprint $table) {

                $table->unsignedInteger('current_revision')
                    ->default(0)
                    ->after('archive_reason');

                $table->index('current_revision');
            });
        }


        /*
        |--------------------------------------------------------------------------
        | Document Versions
        |--------------------------------------------------------------------------
        */

        Schema::create('document_versions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('document_id')
                ->constrained('documents')
                ->restrictOnDelete();

            $table->unsignedInteger(
                'revision_number'
            );

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string(
                'file_name'
            );

            $table->text(
                'file_path'
            );

            $table->string(
                'file_extension',
                20
            )->nullable();

            $table->string(
                'mime_type'
            )->nullable();

            $table->unsignedBigInteger(
                'file_size'
            )->nullable();

            $table->text(
                'revision_note'
            )->nullable();

            $table->json(
                'metadata_snapshot'
            )->nullable();

            $table->timestamps();


            /*
            |--------------------------------------------------------------------------
            | Revision Integrity
            |--------------------------------------------------------------------------
            */

            $table->unique([
                'document_id',
                'revision_number',
            ]);

            $table->index([
                'document_id',
                'created_at',
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | Backfill Existing Documents as REV 0
        |--------------------------------------------------------------------------
        */

        DB::table('documents')
            ->orderBy('id')
            ->chunkById(
                100,
                function ($documents) {

                    $rows = [];


                    foreach ($documents as $document) {

                        $rows[] = [
                            'document_id' =>
                                $document->id,

                            'revision_number' =>
                                0,

                            'uploaded_by' =>
                                $document->uploaded_by,

                            'file_name' =>
                                $document->file_name,

                            'file_path' =>
                                $document->file_path,

                            'file_extension' =>
                                $document->file_extension,

                            'mime_type' =>
                                $document->mime_type,

                            'file_size' =>
                                $document->file_size,

                            'revision_note' =>
                                'Initial document version.',

                            'metadata_snapshot' =>
                                json_encode([
                                    'title' =>
                                        $document->title,

                                    'document_code' =>
                                        $document->document_code,

                                    'part_number' =>
                                        $document->part_number,

                                    'category_id' =>
                                        $document->category_id,

                                    'document_date' =>
                                        $document->document_date,

                                    'description' =>
                                        $document->description,

                                    'keywords' =>
                                        $document->keywords,

                                    'is_active' =>
                                        (bool) $document->is_active,
                                ]),

                            'created_at' =>
                                $document->created_at
                                ?? now(),

                            'updated_at' =>
                                $document->created_at
                                ?? now(),
                        ];
                    }


                    if ($rows !== []) {

                        DB::table(
                            'document_versions'
                        )->insert(
                            $rows
                        );
                    }
                }
            );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'document_versions'
        );


        if (
            Schema::hasColumn(
                'documents',
                'current_revision'
            )
        ) {

            Schema::table(
                'documents',
                function (Blueprint $table) {

                    $table->dropIndex(
                        'documents_current_revision_index'
                    );

                    $table->dropColumn(
                        'current_revision'
                    );
                }
            );
        }
    }
};