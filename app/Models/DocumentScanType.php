<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentScanType extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'sort_order',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
