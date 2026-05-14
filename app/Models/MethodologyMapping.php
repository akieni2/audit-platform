<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MethodologyMapping extends Model
{
    protected $fillable = [
        'methodology_template_id',
        'methodology_control_id',
        'methodology_requirement_id',
        'workflow_template_id',
        'workflow_stage_id',
        'form_template_id',
        'questionnaire_template_id',
        'control_library_id',
        'control_measure_id',
        'taxonomy_term_id',
        'department_id',
        'mapping_type',
        'risk_category',
        'mapping_payload',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'mapping_payload' => 'array',
        ];
    }

    public function methodologyTemplate(): BelongsTo
    {
        return $this->belongsTo(MethodologyTemplate::class);
    }

    public function methodologyControl(): BelongsTo
    {
        return $this->belongsTo(MethodologyControl::class);
    }

    public function methodologyRequirement(): BelongsTo
    {
        return $this->belongsTo(MethodologyRequirement::class);
    }

    public function workflowTemplate(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class);
    }

    public function workflowStage(): BelongsTo
    {
        return $this->belongsTo(WorkflowStage::class);
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class);
    }

    public function questionnaireTemplate(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireTemplate::class);
    }

    public function controlLibrary(): BelongsTo
    {
        return $this->belongsTo(ControlLibrary::class);
    }

    public function controlMeasure(): BelongsTo
    {
        return $this->belongsTo(ControlMeasure::class);
    }

    public function taxonomyTerm(): BelongsTo
    {
        return $this->belongsTo(TaxonomyTerm::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
