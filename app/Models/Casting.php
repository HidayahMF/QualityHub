<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Casting extends Model
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


    public function retoolingsFrom(): HasMany
    {
        return $this->hasMany(
            DocumentRetooling::class,
            'from_casting_id'
        );
    }


    public function retoolingsTo(): HasMany
    {
        return $this->hasMany(
            DocumentRetooling::class,
            'to_casting_id'
        );
    }
}
