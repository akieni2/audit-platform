<?php

namespace App\Models;

use App\Domain\Workflow\Enums\WorkflowExecutionMode;
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
        'ui_component',
        'component_key',
        'configuration_json',
        'validation_rules_json',
        'execution_mode',
        'allow_skip',
        'requires_approval',
        'approval_role_id',
        'questionnaire_template_id',
        'form_template_id',
        'swot_template_id',
        'raci_template_id',
        'form_schema_json',
        'risk_matrix_schema_json',
        'position_x',
        'position_y',
        'color',
        'icon',
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
            'execution_mode' => WorkflowExecutionMode::class,
            'sort_order' => 'integer',
            'configuration' => 'array',
            'configuration_json' => 'array',
            'validation_rules_json' => 'array',
            'form_schema_json' => 'array',
            'risk_matrix_schema_json' => 'array',
            'position_x' => 'integer',
            'position_y' => 'integer',
            'is_required' => 'boolean',
            'is_repeatable' => 'boolean',
            'allow_skip' => 'boolean',
            'requires_approval' => 'boolean',
        ];
    }

    public function workflowTemplate(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class);
    }

    public function questionnaireTemplate(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireTemplate::class)->withTrashed();
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class)->withTrashed();
    }

    public function swotTemplate(): BelongsTo
    {
        return $this->belongsTo(SwotTemplate::class);
    }

    public function raciTemplate(): BelongsTo
    {
        return $this->belongsTo(RaciTemplate::class);
    }

    public function approvalRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'approval_role_id');
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

    public function executionLogs(): HasMany
    {
        return $this->hasMany(WorkflowExecutionLog::class);
    }

    public function formSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class)->orderByDesc('submitted_at')->orderByDesc('id');
    }

    /**
     * @return array<string, mixed>
     */
    public function resolvedConfiguration(): array
    {
        $configuration = $this->configuration_json;

        if (is_array($configuration) && $configuration !== []) {
            return $configuration;
        }

        return is_array($this->configuration) ? $this->configuration : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolvedValidationRules(): array
    {
        return is_array($this->validation_rules_json)
            ? $this->validation_rules_json
            : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolvedFormSchema(): array
    {
        return is_array($this->form_schema_json)
            ? $this->form_schema_json
            : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolvedRiskMatrixSchema(): array
    {
        return is_array($this->risk_matrix_schema_json)
            ? $this->risk_matrix_schema_json
            : [];
    }

    public function resolvedStageType(): ?WorkflowStageType
    {
        return $this->stage_type instanceof WorkflowStageType
            ? $this->stage_type
            : WorkflowStageType::fromMixed($this->stage_type);
    }

    public function resolvedExecutionMode(): ?WorkflowExecutionMode
    {
        if ($this->execution_mode instanceof WorkflowExecutionMode) {
            return $this->execution_mode;
        }

        $mode = WorkflowExecutionMode::fromMixed($this->execution_mode);

        if ($mode instanceof WorkflowExecutionMode) {
            return $mode;
        }

        $type = $this->resolvedStageType();

        return match ($type) {
            WorkflowStageType::Questionnaire => WorkflowExecutionMode::Questionnaire,
            WorkflowStageType::Approval => WorkflowExecutionMode::Approval,
            WorkflowStageType::Form => WorkflowExecutionMode::Form,
            WorkflowStageType::SwotAnalysis, WorkflowStageType::SwotValidation => WorkflowExecutionMode::Swot,
            WorkflowStageType::RaciAssignment, WorkflowStageType::RaciValidation => WorkflowExecutionMode::Raci,
            default => $this->configuration !== null
                ? WorkflowExecutionMode::Automatic
                : WorkflowExecutionMode::Manual,
        };
    }

    public function usesQuestionnaire(): bool
    {
        return $this->questionnaire_template_id !== null
            || $this->resolvedStageType() === WorkflowStageType::Questionnaire
            || $this->resolvedExecutionMode() === WorkflowExecutionMode::Questionnaire;
    }

    public function usesFormTemplate(): bool
    {
        return $this->form_template_id !== null
            || $this->resolvedStageType() === WorkflowStageType::Form
            || $this->resolvedExecutionMode() === WorkflowExecutionMode::Form
            || in_array($this->resolvedComponentKey(), [
                'dynamic_form',
                'dynamic_interview_form',
                'approval_form',
                'risk_capture_form',
            ], true);
    }

    public function resolvedComponentKey(): string
    {
        if (filled($this->component_key)) {
            return (string) $this->component_key;
        }

        if ($this->usesQuestionnaire()) {
            return 'questionnaire_bridge';
        }

        return match ($this->resolvedStageType()) {
            WorkflowStageType::Approval => 'approval_form',
            WorkflowStageType::RiskCapture => 'risk_capture_form',
            WorkflowStageType::Form => 'dynamic_form',
            WorkflowStageType::SwotAnalysis, WorkflowStageType::SwotValidation => 'swot_stage',
            WorkflowStageType::RaciAssignment, WorkflowStageType::RaciValidation => 'raci_stage',
            default => $this->form_template_id !== null ? 'dynamic_form' : 'system_stage',
        };
    }
}