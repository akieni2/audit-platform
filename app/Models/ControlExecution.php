<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlExecution extends Model
{
    protected $fillable = [
        'control_measure_id',
        'mission_id',
        'workflow_instance_id',
        'workflow_stage_execution_id',
        'executed_by',
        'status',
        'score',
        'notes',
        'metadata',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'metadata' => 'array',
            'executed_at' => 'datetime',
        ];
    }

    public function controlMeasure(): BelongsTo
    {
        return $this->belongsTo(ControlMeasure::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function workflowStageExecution(): BelongsTo
    {
        return $this->belongsTo(WorkflowStageExecution::class);
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by')->withTrashed();
    }
}
