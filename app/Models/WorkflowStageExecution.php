<?php

namespace App\Models;

use App\Domain\Workflow\Enums\WorkflowStageExecutionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStageExecution extends Model
{
    protected $fillable = [
        'workflow_instance_id',
        'workflow_stage_id',
        'status',
        'started_at',
        'completed_at',
        'assigned_to',
        'payload',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => WorkflowStageExecutionStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function workflowStage(): BelongsTo
    {
        return $this->belongsTo(WorkflowStage::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')->withTrashed();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WorkflowExecutionLog::class);
    }
}
