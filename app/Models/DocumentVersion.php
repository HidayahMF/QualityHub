<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'document_id',
        'revision_number',
        'uploaded_by',

        'file_name',
        'file_path',
        'file_extension',
        'mime_type',
        'file_size',

        'revision_note',
        'metadata_snapshot',
    ];


    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'revision_number' =>
            'integer',

        'file_size' =>
            'integer',

        'metadata_snapshot' =>
            'array',
    ];


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function document(): BelongsTo
    {
        return $this->belongsTo(
            Document::class
        );
    }


    public function uploader(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Revision Helpers
    |--------------------------------------------------------------------------
    */

    public function getRevisionLabel(): string
    {
        return 'REV '
            . $this->revision_number;
    }


    public function isCurrent(): bool
    {
        return (int) $this->revision_number
            === (int) $this->document?->current_revision;
    }


    /*
    |--------------------------------------------------------------------------
    | File Helpers
    |--------------------------------------------------------------------------
    */

    public function getFileTypeLabel(): string
    {
        return strtoupper(
            $this->file_extension
            ?: 'FILE'
        );
    }


    public function getFormattedFileSize(): string
    {
        $bytes = (int) $this->file_size;


        if ($bytes <= 0) {
            return '-';
        }


        if ($bytes >= 1073741824) {

            return number_format(
                $bytes / 1073741824,
                2
            ) . ' GB';
        }


        if ($bytes >= 1048576) {

            return number_format(
                $bytes / 1048576,
                2
            ) . ' MB';
        }


        if ($bytes >= 1024) {

            return number_format(
                $bytes / 1024,
                1
            ) . ' KB';
        }


        return number_format(
            $bytes
        ) . ' B';
    }
}