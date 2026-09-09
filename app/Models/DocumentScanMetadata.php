<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentScanMetadata extends Model
{
    protected $table = 'document_scan_metadata';

    protected $fillable = [
        'document_id',
        'source_type',
        'scan_type',
        'scan_date',
        'shift',
        'line',
        'from_type',
        'to_type',
        'scan_time',
        'image_format',
        'capture_source',
    ];

    protected $casts = [
        'scan_date' => 'date',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
