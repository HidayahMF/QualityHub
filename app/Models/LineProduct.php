<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineProduct extends Model
{
    protected $fillable = [
        'production_line_id',
        'casting_id',
        'cycle_time',
        'is_active',
    ];


    protected function casts(): array
    {
        return [
            'cycle_time' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }


    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(
            ProductionLine::class
        );
    }


    public function casting(): BelongsTo
    {
        return $this->belongsTo(
            Casting::class
        );
    }
}
