<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionRiskProjection extends Model
{
    protected $fillable = [
        'mission_id',
        'intake_detected_count',
        'intake_reviewed_count',
        'intake_qualified_count',
        'intake_approved_count',
        'intake_promoted_count',
        'official_count',
        'official_critical_count',
        'official_residual_critical_count',
        'inherent_heatmap',
        'residual_heatmap',
        'source_signature',
        'source_record_count',
        'refresh_count',
        'integrity_status',
        'last_integrity_checked_at',
        'refreshed_at',
    ];

    protected function casts(): array
    {
        return [
            'inherent_heatmap' => 'array',
            'residual_heatmap' => 'array',
            'last_integrity_checked_at' => 'datetime',
            'refreshed_at' => 'datetime',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }
}
