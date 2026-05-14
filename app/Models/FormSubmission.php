<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    protected $fillable = [
        'form_template_id',
        'workflow_stage_id',
        'workflow_stage_execution_id',
        'workflow_instance_id',
        'mission_id',
        'entretien_id',
        'submitted_by',
        'submitted_at',
        'status',
        'submission_payload',
        'form_snapshot',
        'validation_errors_json',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'submission_payload' => 'array',
            'form_snapshot' => 'array',
            'validation_errors_json' => 'array',
        ];
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class)->withTrashed();
    }

    public function workflowStage(): BelongsTo
    {
        return $this->belongsTo(WorkflowStage::class);
    }

    public function workflowStageExecution(): BelongsTo
    {
        return $this->belongsTo(WorkflowStageExecution::class);
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function entretien(): BelongsTo
    {
        return $this->belongsTo(Entretien::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by')->withTrashed();
    }
}
