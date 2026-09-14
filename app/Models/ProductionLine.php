<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionLine extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];


    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }


    public function lineProducts(): HasMany
    {
        return $this->hasMany(
            LineProduct::class
        );
    }


    public function documentRetoolings(): HasMany
    {
        return $this->hasMany(
            DocumentRetooling::class
        );
    }
}
