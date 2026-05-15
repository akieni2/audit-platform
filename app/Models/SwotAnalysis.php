<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SwotAnalysis extends Model
{
    protected $fillable = [
        'swot_template_id',
        'mission_id',
        'department_id',
        'workflow_instance_id',
        'workflow_stage_execution_id',
        'analysis_scope',
        'status',
        'score',
        'weighted_score',
        'priority_index',
        'analysis_payload',
        'concluded_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'weighted_score' => 'decimal:2',
            'priority_index' => 'decimal:2',
            'analysis_payload' => 'array',
            'concluded_at' => 'datetime',
        ];
    }

    public function swotTemplate(): BelongsTo
    {
        return $this->belongsTo(SwotTemplate::class);
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

    public function workflowStageExecution(): BelongsTo
    {
        return $this->belongsTo(WorkflowStageExecution::class);
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(SwotRecommendation::class)->orderByDesc('priority_index')->orderByDesc('id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(SwotSnapshot::class)->orderByDesc('id');
    }
}
