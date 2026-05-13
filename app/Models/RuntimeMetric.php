<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RuntimeMetric extends Model
{
    protected $fillable = [
        'metric_key',
        'metric_type',
        'scope_type',
        'scope_id',
        'dimensions_hash',
        'dimensions',
        'value',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'dimensions' => 'array',
            'value' => 'decimal:6',
            'recorded_at' => 'datetime',
        ];
    }
}
