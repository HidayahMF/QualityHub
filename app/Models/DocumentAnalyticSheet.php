<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAnalyticSheet extends Model
{
    protected $table = 'document_analytic_sheets';

    protected $fillable = [
        'document_analytics_id',
        'sheet_name',
        'sheet_index',
        'row_count',
        'column_count',
        'headers',
        'preview_data',
        'statistics',
        'chart_suggestions',
    ];

    protected $casts = [
        'sheet_index' => 'integer',
        'row_count' => 'integer',
        'column_count' => 'integer',
        'headers' => 'array',
        'preview_data' => 'array',
        'statistics' => 'array',
        'chart_suggestions' => 'array',
    ];

    public function analytics(): BelongsTo
    {
        return $this->belongsTo(
            DocumentAnalytics::class,
            'document_analytics_id'
        );
    }
}
