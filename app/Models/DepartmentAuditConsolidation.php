<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentAuditConsolidation extends Model
{
    protected $fillable = [
        'mission_id',
        'department_id',
        'synthesis',
        'global_risk_level',
        'key_findings',
        'recommendations',
        'generated_by_ai',
        'validated_by',
    ];

    protected function casts(): array
    {
        return [
            'generated_by_ai' => 'boolean',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
