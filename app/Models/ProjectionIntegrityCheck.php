<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectionIntegrityCheck extends Model
{
    protected $fillable = [
        'projection_type',
        'scope_type',
        'scope_id',
        'status',
        'correlation_id',
        'expected_signature',
        'actual_signature',
        'mismatch_count',
        'expected_payload',
        'actual_payload',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'expected_payload' => 'array',
            'actual_payload' => 'array',
            'checked_at' => 'datetime',
        ];
    }
}
