<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
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

    protected $casts = [
        'document_date' => 'date',
        'file_size' => 'integer',
        'is_active' => 'boolean',
        'archived_at' => 'datetime',
        'current_revision' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(DocumentVersion::class)->ofMany(
            'revision_number',
            'max'
        );
    }

    /**
     * Metadata khusus untuk dokumen yang berasal dari fitur Scan / Capture QC.
     */
    public function scanMetadata(): HasOne
    {
        return $this->hasOne(DocumentScanMetadata::class);
    }

    public function axleProduction(): HasOne
    {
        return $this->hasOne(DocumentAxleProduction::class);
    }

    public function brakeProduction(): HasOne
    {
        return $this->hasOne(DocumentBrakeProduction::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function isArchived(): bool
    {
        return $this->is_active === false;
    }

    public function getCurrentRevisionLabel(): string
    {
        return 'REV ' . (int) $this->current_revision;
    }

    public function isFavoritedBy(?int $userId): bool
    {
        if (! $userId) {
            return false;
        }

        return $this->favorites()
            ->where('user_id', $userId)
            ->exists();
    }

    public function getFileTypeLabel(): string
    {
        return strtoupper($this->file_extension ?: 'FILE');
    }

    public function getFormattedFileSize(): string
    {
        $bytes = (int) $this->file_size;

        if ($bytes <= 0) {
            return '-';
        }

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return number_format($bytes) . ' B';
    }
}
