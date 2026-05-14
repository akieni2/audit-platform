<?php

namespace App\Models;

use App\Domain\Workflow\Enums\WorkflowStageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStage extends Model
{
    protected $fillable = [
        'workflow_template_id',
        'name',
        'code',
        'description',
        'stage_type',
        'sort_order',
        'configuration',
        'is_required',
        'is_repeatable',
        'role_scope',
    ];

    protected function casts(): array
    {
        return [
            'stage_type' => WorkflowStageType::class,
            'sort_order' => 'integer',
            'configuration' => 'array',
            'is_required' => 'boolean',
            'is_repeatable' => 'boolean',
        ];
    }

    public function workflowTemplate(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class);
    }

    public function incomingTransitions(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class, 'to_stage_id');
    }

    public function outgoingTransitions(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class, 'from_stage_id');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(WorkflowStageExecution::class);
    }
}
