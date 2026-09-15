<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BrakePartNumber extends Model
{
    protected $fillable = [
        'prefix',
        'variant',
        'display_name',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function documentBrakeProductions(): HasMany
    {
        return $this->hasMany(DocumentBrakeProduction::class);
    }
}
