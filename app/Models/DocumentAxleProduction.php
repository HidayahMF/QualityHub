<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAxleProduction extends Model
{
    protected $fillable = [
        'document_id',
        'axle_type_id',
        'axle_position_id',
        'production_line_id',
        'shift',
        'production_time',
        'snapshot_axle_type_code',
        'snapshot_axle_type_name',
        'snapshot_position_code',
        'snapshot_position_name',
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

    public function axleType(): BelongsTo
    {
        return $this->belongsTo(AxleType::class);
    }

    public function axlePosition(): BelongsTo
    {
        return $this->belongsTo(AxlePosition::class);
    }

    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }
}
