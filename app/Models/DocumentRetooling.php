<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRetooling extends Model
{
    protected $fillable = [
        'document_id',
        'shift',
        'production_line_id',
        'from_casting_id',
        'to_casting_id',
        'from_cycle_time',
        'to_cycle_time',
        'retooling_date',
    ];


    protected function casts(): array
    {
        return [
            'shift' => 'integer',
            'from_cycle_time' => 'decimal:2',
            'to_cycle_time' => 'decimal:2',
            'retooling_date' => 'immutable_date',
        ];
    }


    public function document(): BelongsTo
    {
        return $this->belongsTo(
            Document::class
        );
    }


    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(
            ProductionLine::class
        );
    }


    public function fromCasting(): BelongsTo
    {
        return $this->belongsTo(
            Casting::class,
            'from_casting_id'
        );
    }


    public function toCasting(): BelongsTo
    {
        return $this->belongsTo(
            Casting::class,
            'to_casting_id'
        );
    }
}
