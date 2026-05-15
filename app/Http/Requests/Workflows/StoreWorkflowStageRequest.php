<?php

namespace App\Http\Requests\Workflows;

use App\Domain\Workflow\Enums\WorkflowExecutionMode;
use App\Domain\Workflow\Enums\WorkflowStageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkflowStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => filled($this->input('code'))
                ? strtoupper(trim((string) $this->input('code')))
                : strtoupper(\Illuminate\Support\Str::slug((string) $this->input('name'), '_')),
            'approval_role_id' => filled($this->input('approval_role_id')) ? (int) $this->input('approval_role_id') : null,
            'questionnaire_template_id' => filled($this->input('questionnaire_template_id')) ? (int) $this->input('questionnaire_template_id') : null,
            'form_template_id' => filled($this->input('form_template_id')) ? (int) $this->input('form_template_id') : null,
            'swot_template_id' => filled($this->input('swot_template_id')) ? (int) $this->input('swot_template_id') : null,
            'raci_template_id' => filled($this->input('raci_template_id')) ? (int) $this->input('raci_template_id') : null,
            'component_key' => filled($this->input('component_key')) ? trim((string) $this->input('component_key')) : null,
            'position_x' => filled($this->input('position_x')) ? (int) $this->input('position_x') : 0,
            'position_y' => filled($this->input('position_y')) ? (int) $this->input('position_y') : 0,
            'sort_order' => filled($this->input('sort_order')) ? (int) $this->input('sort_order') : 0,
            'allow_skip' => $this->boolean('allow_skip'),
            'requires_approval' => $this->boolean('requires_approval'),
            'is_required' => $this->boolean('is_required', true),
            'is_repeatable' => $this->boolean('is_repeatable'),
            'configuration_json' => $this->decodeJsonField('configuration_json_text'),
            'validation_rules_json' => $this->decodeJsonField('validation_rules_json_text'),
            'form_schema_json' => $this->decodeJsonField('form_schema_json_text'),
            'risk_matrix_schema_json' => $this->decodeJsonField('risk_matrix_schema_json_text'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
            'stage_type' => ['required', Rule::in(WorkflowStageType::values())],
            'ui_component' => ['nullable', 'string', 'max:80'],
            'component_key' => ['nullable', 'string', 'max:80'],
            'configuration_json' => ['nullable', 'array'],
            'validation_rules_json' => ['nullable', 'array'],
            'execution_mode' => ['nullable', Rule::in(array_keys(WorkflowExecutionMode::labels()))],
            'allow_skip' => ['nullable', 'boolean'],
            'requires_approval' => ['nullable', 'boolean'],
            'approval_role_id' => ['nullable', 'exists:roles,id'],
            'questionnaire_template_id' => ['nullable', 'exists:questionnaire_templates,id'],
            'form_template_id' => ['nullable', 'exists:form_templates,id'],
            'swot_template_id' => ['nullable', 'exists:swot_templates,id'],
            'raci_template_id' => ['nullable', 'exists:raci_templates,id'],
            'form_schema_json' => ['nullable', 'array'],
            'risk_matrix_schema_json' => ['nullable', 'array'],
            'position_x' => ['nullable', 'integer'],
            'position_y' => ['nullable', 'integer'],
            'color' => ['nullable', 'string', 'max:32'],
            'icon' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_required' => ['nullable', 'boolean'],
            'is_repeatable' => ['nullable', 'boolean'],
            'role_scope' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function decodeJsonField(string $key): ?array
    {
        $value = trim((string) $this->input($key, ''));
        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
