<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlEvidence extends Model
{
    protected $fillable = [
        'control_measure_id',
        'mission_id',
        'workflow_instance_id',
        'form_submission_id',
        'collected_by',
        'evidence_type',
        'title',
        'document_path',
        'notes',
        'metadata',
        'collected_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'collected_at' => 'datetime',
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

    public function formSubmission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by')->withTrashed();
    }
}
