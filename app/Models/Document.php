<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'category_id',
        'uploaded_by',

        'title',
        'document_code',
        'part_number',

        'file_name',
        'file_path',
        'file_extension',
        'mime_type',
        'file_size',

        'document_date',
        'description',
        'keywords',

        'is_active',

        'archived_at',
        'archived_by',
        'archive_reason',

        'current_revision',
    ];


    /*
    |--------------------------------------------------------------------------
    | Attribute Casting
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'document_date' => 'date',

        'file_size' => 'integer',

        'is_active' => 'boolean',

        'archived_at' => 'datetime',

        'current_revision' => 'integer',
    ];


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Category dokumen.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(
            Category::class
        );
    }


    /**
     * User yang pertama kali mengupload dokumen.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by'
        );
    }


    /**
     * User/Admin yang mengarsipkan dokumen.
     */
    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'archived_by'
        );
    }


    /**
     * Favorite milik seluruh user untuk dokumen ini.
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(
            Favorite::class
        );
    }


    /**
     * Activity log dokumen.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(
            ActivityLog::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Document Versions
    |--------------------------------------------------------------------------
    */

    /**
     * Seluruh revision history dokumen.
     *
     * REV 0
     * REV 1
     * REV 2
     * ...
     */
    public function versions(): HasMany
    {
        return $this->hasMany(
            DocumentVersion::class
        );
    }


    /**
     * Revision terbaru berdasarkan revision_number terbesar.
     */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(
            DocumentVersion::class
        )->ofMany(
            'revision_number',
            'max'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(
        Builder $query
    ): Builder {

        return $query->where(
            'is_active',
            true
        );
    }


    public function scopeArchived(
        Builder $query
    ): Builder {

        return $query->where(
            'is_active',
            false
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Status Helpers
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->is_active === true;
    }


    public function isArchived(): bool
    {
        return $this->is_active === false;
    }


    /*
    |--------------------------------------------------------------------------
    | Revision Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Contoh:
     *
     * REV 0
     * REV 1
     * REV 2
     */
    public function getCurrentRevisionLabel(): string
    {
        return 'REV '
            . (int) $this->current_revision;
    }


    /*
    |--------------------------------------------------------------------------
    | Favorite Helpers
    |--------------------------------------------------------------------------
    */

    public function isFavoritedBy(
        ?int $userId
    ): bool {

        if (! $userId) {
            return false;
        }


        return $this
            ->favorites()
            ->where(
                'user_id',
                $userId
            )
            ->exists();
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