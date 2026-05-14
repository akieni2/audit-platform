<?php

namespace App\Models;

use App\Domain\Workflow\Enums\WorkflowInstanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowInstance extends Model
{
    protected $fillable = [
        'workflow_template_id',
        'mission_id',
        'current_stage_id',
        'status',
        'started_at',
        'completed_at',
        'created_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => WorkflowInstanceStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function workflowTemplate(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class);
    }

    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(WorkflowStage::class, 'current_stage_id');
    }

    public function stageExecutions(): HasMany
    {
        return $this->hasMany(WorkflowStageExecution::class)->orderBy('id');
    }

    public function executionLogs(): HasMany
    {
        return $this->hasMany(WorkflowExecutionLog::class)->orderByDesc('occurred_at');
    }

    public function formSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class)->orderByDesc('submitted_at')->orderByDesc('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
