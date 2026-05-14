<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowTransition extends Model
{
    protected $fillable = [
        'workflow_template_id',
        'from_stage_id',
        'to_stage_id',
        'condition_type',
        'condition_configuration',
        'role_required',
        'is_automatic',
    ];

    protected function casts(): array
    {
        return [
            'condition_configuration' => 'array',
            'is_automatic' => 'boolean',
        ];
    }

    public function workflowTemplate(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class);
    }

    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(WorkflowStage::class, 'from_stage_id');
    }

    public function toStage(): BelongsTo
    {
        return $this->belongsTo(WorkflowStage::class, 'to_stage_id');
    }
}
