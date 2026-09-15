<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentBrakeProduction extends Model
{
    protected $fillable = [
        'document_id',
        'brake_part_number_id',
        'production_line_id',
        'shift',
        'production_time',
        'snapshot_prefix',
        'snapshot_variant',
        'snapshot_display_name',
        'snapshot_line_code',
        'snapshot_line_name',
    ];

    protected function casts(): array
    {
        return [
            'shift' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function brakePartNumber(): BelongsTo
    {
        return $this->belongsTo(BrakePartNumber::class);
    }

    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }
}
