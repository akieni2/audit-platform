<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SwotSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'swot_template_id',
        'swot_analysis_id',
        'mission_id',
        'department_id',
        'workflow_instance_id',
        'snapshot_hash',
        'snapshot_payload',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_payload' => 'array',
            'captured_at' => 'datetime',
        ];
    }

    public function swotTemplate(): BelongsTo
    {
        return $this->belongsTo(SwotTemplate::class);
    }

    public function swotAnalysis(): BelongsTo
    {
        return $this->belongsTo(SwotAnalysis::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }
}
