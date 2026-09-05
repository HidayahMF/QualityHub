<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentAnalytics extends Model
{
    protected $table = 'document_analytics';

    protected $fillable = [
        'document_id',
        'document_version_id',
        'revision_number',
        'status',
        'sheet_count',
        'total_rows',
        'total_columns',
        'numeric_cells',
        'blank_cells',
        'summary',
        'schema',
        'processed_at',
        'error_message',
    ];

    protected $casts = [
        'revision_number' => 'integer',
        'sheet_count' => 'integer',
        'total_rows' => 'integer',
        'total_columns' => 'integer',
        'numeric_cells' => 'integer',
        'blank_cells' => 'integer',
        'summary' => 'array',
        'schema' => 'array',
        'processed_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(
            Document::class
        );
    }

    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(
            DocumentVersion::class
        );
    }

    public function sheets(): HasMany
    {
        return $this->hasMany(
            DocumentAnalyticSheet::class,
            'document_analytics_id'
        )
            ->orderBy('sheet_index');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }
}
