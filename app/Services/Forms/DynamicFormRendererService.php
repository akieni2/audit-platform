<?php

namespace App\Services\Forms;

use App\Models\Department;
use App\Models\Entretien;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\IdentifiedRisk;
use App\Models\MissionDocument;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStage;
use App\Models\WorkflowStageExecution;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class DynamicFormRendererService
{
    /**
     * @return array{
     *   snapshot: array<string, mixed>,
     *   values: array<string, mixed>,
     *   visible_fields: list<array<string, mixed>>,
     *   current_submission: ?FormSubmission
     * }
     */
    public function buildViewData(
        WorkflowInstance $instance,
        WorkflowStage $stage,
        ?Entretien $entretien = null,
    ): array {
        $snapshot = $this->resolveSnapshot($stage);
        $submission = $this->resolveCurrentSubmission($instance, $stage, $entretien);
        $values = is_array($submission?->submission_payload) ? ($submission->submission_payload['fields'] ?? []) : [];
        $values = array_replace($this->defaultValues($snapshot), is_array($values) ? $values : []);

        $visibleFields = [];
        foreach (($snapshot['template']['fields'] ?? []) as $field) {
            $fieldValues = array_replace($values, [$field['field_key'] => $values[$field['field_key']] ?? ($field['default_value'] ?? null)]);
            if (! $this->isFieldVisible($field, $fieldValues)) {
                continue;
            }

            $field['runtime_options'] = $this->runtimeOptions($instance, $field);
            $visibleFields[] = $field;
        }

        return [
            'snapshot' => $snapshot,
            'values' => $values,
            'visible_fields' => $visibleFields,
            'current_submission' => $submission,
        ];
    }

    /**
     * @return array{submission:FormSubmission,payload:array<string,mixed>,finalized:bool}
     */
    public function persistSubmission(
        Request $request,
        WorkflowInstance $instance,
        WorkflowStage $stage,
        WorkflowStageExecution $execution,
        ?User $actor = null,
        ?Entretien $entretien = null,
    ): array {
        $snapshot = $this->resolveSnapshot($stage);
        $isFinalized = $request->input('action', 'complete') !== 'save';

        $validator = $this->buildValidator($request, $instance, $snapshot, $isFinalized);
        $validator->validate();

        $normalized = $this->normalizePayload(
            $request,
            $instance,
            $stage,
            $snapshot,
            $validator->validated(),
            $actor,
            $entretien,
        );

        $submission = $this->resolveCurrentSubmission($instance, $stage, $entretien);
        if (! $submission instanceof FormSubmission) {
            $submission = new FormSubmission();
            $submission->workflow_instance_id = $instance->id;
            $submission->workflow_stage_id = $stage->id;
            $submission->mission_id = $instance->mission_id;
        }

        $submission->forceFill([
            'form_template_id' => $stage->form_template_id,
            'workflow_stage_execution_id' => $execution->id,
            'entretien_id' => $entretien?->id,
            'submitted_by' => $actor?->id,
            'submitted_at' => now(),
            'status' => $isFinalized ? FormSubmission::STATUS_SUBMITTED : FormSubmission::STATUS_DRAFT,
            'submission_payload' => $normalized,
            'form_snapshot' => $snapshot,
            'validation_errors_json' => [],
        ])->save();

        $normalized['form_submission_id'] = (int) $submission->id;

        return [
            'submission' => $submission->fresh(),
            'payload' => $normalized,
            'finalized' => $isFinalized,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveSnapshot(WorkflowStage $stage): array
    {
        if ($stage->form_template_id !== null) {
            $stage->loadMissing(['formTemplate.fields.options' => fn ($query) => $query->orderBy('sort_order')]);
            $template = $stage->formTemplate;

            if (! $template instanceof FormTemplate) {
                throw new InvalidArgumentException('Le stage référence un formulaire introuvable.');
            }

            return $this->compileTemplateSnapshot($template);
        }

        return $this->compileInlineSnapshot($stage);
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultValues(array $snapshot): array
    {
        $defaults = [];
        foreach (($snapshot['template']['fields'] ?? []) as $field) {
            $defaults[$field['field_key']] = $field['default_value'] ?? null;
        }

        return $defaults;
    }

    public function isFieldVisible(array $field, array $values): bool
    {
        $rules = $field['conditional_rules'] ?? [];
        if (! is_array($rules) || $rules === []) {
            return true;
        }

        $ruleSet = $rules['rules'] ?? [$rules];
        $match = strtolower((string) ($rules['match'] ?? 'all'));
        $results = [];

        foreach ($ruleSet as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $dependsOn = (string) ($rule['depends_on'] ?? '');
            $operator = strtolower((string) ($rule['operator'] ?? 'equals'));
            $expected = $rule['value'] ?? null;
            $actual = Arr::get($values, $dependsOn);

            $results[] = match ($operator) {
                'equals' => $actual == $expected,
                'not_equals' => $actual != $expected,
                'in' => in_array($actual, Arr::wrap($expected), true),
                'not_in' => ! in_array($actual, Arr::wrap($expected), true),
                'truthy' => filled($actual) && $actual !== false && $actual !== '0',
                'falsy' => blank($actual) || $actual === false || $actual === '0',
                default => true,
            };
        }

        if ($results === []) {
            return true;
        }

        return $match === 'any'
            ? in_array(true, $results, true)
            : ! in_array(false, $results, true);
    }

    private function resolveCurrentSubmission(
        WorkflowInstance $instance,
        WorkflowStage $stage,
        ?Entretien $entretien = null,
    ): ?FormSubmission {
        if (! Schema::hasTable('form_submissions')) {
            return null;
        }

        return FormSubmission::query()
            ->where('workflow_instance_id', $instance->id)
            ->where('workflow_stage_id', $stage->id)
            ->when($entretien instanceof Entretien, fn ($query) => $query->where('entretien_id', $entretien->id))
            ->latest('id')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function compileTemplateSnapshot(FormTemplate $template): array
    {
        $template->loadMissing(['fields.options' => fn ($query) => $query->orderBy('sort_order')]);

        $fields = $template->fields->where('active', true)->sortBy('sort_order')->values()->map(function (FormField $field) {
            return [
                'id' => (int) $field->id,
                'field_key' => (string) $field->field_key,
                'label' => (string) $field->label,
                'help_text' => $field->help_text,
                'field_type' => (string) $field->field_type,
                'placeholder' => $field->placeholder,
                'default_value' => $field->resolvedDefaultValue(),
                'configuration' => $field->configuration_json ?? [],
                'validation_rules' => $field->validation_rules_json ?? [],
                'conditional_rules' => $field->conditional_rules_json ?? [],
                'sort_order' => (int) $field->sort_order,
                'is_required' => (bool) $field->is_required,
                'is_repeatable' => (bool) $field->is_repeatable,
                'options' => $field->options->sortBy('sort_order')->values()->map(fn ($option) => [
                    'label' => $option->label,
                    'value' => $option->value,
                    'sort_order' => (int) $option->sort_order,
                    'is_default' => (bool) $option->is_default,
                ])->all(),
            ];
        })->all();

        $payload = [
            'meta' => [
                'captured_at' => now()->toIso8601String(),
                'template_id' => (int) $template->id,
                'template_name' => (string) $template->name,
                'template_slug' => (string) $template->slug,
                'template_version' => (int) ($template->version ?? 1),
                'signature_hash' => $template->signature_hash,
            ],
            'template' => [
                'id' => (int) $template->id,
                'name' => (string) $template->name,
                'slug' => (string) $template->slug,
                'description' => $template->description,
                'component_key' => $template->component_key ?: 'dynamic_form',
                'fields' => $fields,
            ],
        ];

        $payload['meta']['hash'] = sha1(json_encode($payload['template'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function compileInlineSnapshot(WorkflowStage $stage): array
    {
        $fields = collect($stage->resolvedFormSchema()['fields'] ?? [])
            ->values()
            ->map(function ($field, $index) {
                $field = is_array($field) ? $field : [];

                return [
                    'id' => (int) ($field['id'] ?? $index + 1),
                    'field_key' => (string) ($field['field_key'] ?? $field['code'] ?? 'FIELD_'.$index),
                    'label' => (string) ($field['label'] ?? $field['name'] ?? 'Champ'),
                    'help_text' => $field['help_text'] ?? null,
                    'field_type' => (string) ($field['field_type'] ?? FormField::TYPE_TEXT),
                    'placeholder' => $field['placeholder'] ?? null,
                    'default_value' => $field['default_value'] ?? null,
                    'configuration' => $field['configuration'] ?? [],
                    'validation_rules' => $field['validation_rules'] ?? [],
                    'conditional_rules' => $field['conditional_rules'] ?? [],
                    'sort_order' => (int) ($field['sort_order'] ?? $index),
                    'is_required' => (bool) ($field['is_required'] ?? false),
                    'is_repeatable' => (bool) ($field['is_repeatable'] ?? false),
                    'options' => collect($field['options'] ?? [])
                        ->map(function ($option, $optionIndex) {
                            if (is_array($option)) {
                                return [
                                    'label' => (string) ($option['label'] ?? $option['value'] ?? 'Option'),
                                    'value' => (string) ($option['value'] ?? $option['label'] ?? $optionIndex),
                                    'sort_order' => (int) ($option['sort_order'] ?? $optionIndex),
                                    'is_default' => (bool) ($option['is_default'] ?? false),
                                ];
                            }

                            return [
                                'label' => (string) $option,
                                'value' => (string) $option,
                                'sort_order' => (int) $optionIndex,
                                'is_default' => false,
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy('sort_order')
            ->values()
            ->all();

        $payload = [
            'meta' => [
                'captured_at' => now()->toIso8601String(),
                'template_id' => null,
                'template_name' => $stage->name,
                'template_slug' => $stage->code,
                'template_version' => 1,
                'signature_hash' => null,
            ],
            'template' => [
                'id' => null,
                'name' => $stage->name,
                'slug' => $stage->code,
                'description' => $stage->description,
                'component_key' => $stage->resolvedComponentKey(),
                'fields' => $fields,
            ],
        ];

        $payload['meta']['hash'] = sha1(json_encode($payload['template'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');

        return $payload;
    }

    private function buildValidator(
        Request $request,
        WorkflowInstance $instance,
        array $snapshot,
        bool $requireCompletion,
    ): ValidatorContract {
        $values = $this->extractInputValues($request, $snapshot);
        $rules = [];

        foreach (($snapshot['template']['fields'] ?? []) as $field) {
            if (! $this->isFieldVisible($field, $values)) {
                continue;
            }

            $rules = array_replace($rules, $this->rulesForField($instance, $field, $requireCompletion));
        }

        return Validator::make(array_replace_recursive($request->all(), $request->allFiles()), $rules);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizePayload(
        Request $request,
        WorkflowInstance $instance,
        WorkflowStage $stage,
        array $snapshot,
        array $validated,
        ?User $actor = null,
        ?Entretien $entretien = null,
    ): array {
        $fieldsPayload = [];

        foreach (($snapshot['template']['fields'] ?? []) as $field) {
            if (! $this->isFieldVisible($field, $validated)) {
                continue;
            }

            $key = $field['field_key'];
            $value = $validated[$key] ?? null;

            if ($field['field_type'] === FormField::TYPE_FILE) {
                $value = $this->persistFiles($request, $instance, $stage, $field, $actor, $entretien);
            }

            if (in_array($field['field_type'], [FormField::TYPE_USER_SELECTOR, FormField::TYPE_DEPARTMENT_SELECTOR, FormField::TYPE_RISK_SELECTOR], true)) {
                $value = $this->normalizeSelectorValue($field, $value);
            }

            $fieldsPayload[$key] = $value;
        }

        return [
            'stage_code' => $stage->code,
            'component_key' => $stage->resolvedComponentKey(),
            'submitted_at' => now()->toIso8601String(),
            'fields' => $fieldsPayload,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractInputValues(Request $request, array $snapshot): array
    {
        $values = [];

        foreach (($snapshot['template']['fields'] ?? []) as $field) {
            $key = $field['field_key'];

            if ($field['field_type'] === FormField::TYPE_FILE) {
                $values[$key] = $request->hasFile($key) ? $request->file($key) : null;
                continue;
            }

            $values[$key] = $request->input($key, $field['default_value'] ?? null);
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    private function rulesForField(WorkflowInstance $instance, array $field, bool $requireCompletion): array
    {
        $key = (string) $field['field_key'];
        $rules = Arr::wrap($field['validation_rules'] ?? []);
        $requiredRule = ($requireCompletion && ($field['is_required'] ?? false)) ? ['required'] : ['nullable'];

        return match ($field['field_type']) {
            FormField::TYPE_TEXT, FormField::TYPE_TEXTAREA => [$key => [...$requiredRule, 'string', ...$rules]],
            FormField::TYPE_NUMBER => [$key => [...$requiredRule, 'numeric', ...$rules]],
            FormField::TYPE_DATE, FormField::TYPE_DATETIME => [$key => [...$requiredRule, 'date', ...$rules]],
            FormField::TYPE_BOOLEAN => [$key => [...$requiredRule, 'boolean', ...$rules]],
            FormField::TYPE_SELECT, FormField::TYPE_RADIO => [$key => [...$requiredRule, Rule::in($this->allowedOptionValues($field)), ...$rules]],
            FormField::TYPE_MULTISELECT, FormField::TYPE_CHECKBOX => [
                $key => [...$requiredRule, 'array', ...$rules],
                $key.'.*' => [Rule::in($this->allowedOptionValues($field))],
            ],
            FormField::TYPE_FILE => [$key => [...$requiredRule, 'file', ...$rules]],
            FormField::TYPE_USER_SELECTOR => $this->selectorRules($key, $field, 'users', $requireCompletion),
            FormField::TYPE_DEPARTMENT_SELECTOR => $this->selectorRules($key, $field, 'departments', $requireCompletion),
            FormField::TYPE_RISK_SELECTOR => $this->riskSelectorRules($instance, $key, $field, $requireCompletion),
            default => [$key => [...$requiredRule, ...$rules]],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function selectorRules(string $key, array $field, string $table, bool $requireCompletion): array
    {
        $multiple = (bool) data_get($field, 'configuration.multiple', false);
        $requiredRule = ($requireCompletion && ($field['is_required'] ?? false)) ? ['required'] : ['nullable'];

        if ($multiple) {
            return [
                $key => [...$requiredRule, 'array'],
                $key.'.*' => ['integer', Rule::exists($table, 'id')],
            ];
        }

        return [
            $key => [...$requiredRule, 'integer', Rule::exists($table, 'id')],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function riskSelectorRules(WorkflowInstance $instance, string $key, array $field, bool $requireCompletion): array
    {
        $multiple = (bool) data_get($field, 'configuration.multiple', true);
        $requiredRule = ($requireCompletion && ($field['is_required'] ?? false)) ? ['required'] : ['nullable'];
        $baseQuery = IdentifiedRisk::query()->where('mission_id', $instance->mission_id);

        if ($multiple) {
            return [
                $key => [...$requiredRule, 'array'],
                $key.'.*' => ['integer', Rule::in($baseQuery->pluck('id')->map(fn ($id) => (int) $id)->all())],
            ];
        }

        return [
            $key => [...$requiredRule, 'integer', Rule::in($baseQuery->pluck('id')->map(fn ($id) => (int) $id)->all())],
        ];
    }

    /**
     * @return list<string>
     */
    private function allowedOptionValues(array $field): array
    {
        return collect($field['options'] ?? [])
            ->map(fn ($option) => (string) ($option['value'] ?? $option['label'] ?? ''))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function runtimeOptions(WorkflowInstance $instance, array $field): array
    {
        return match ($field['field_type']) {
            FormField::TYPE_USER_SELECTOR => User::query()
                ->where('active', true)
                ->orderBy('name')
                ->limit(50)
                ->get()
                ->map(fn (User $user) => ['value' => $user->id, 'label' => $user->displayName()])
                ->all(),
            FormField::TYPE_DEPARTMENT_SELECTOR => Department::query()
                ->where('active', true)
                ->orderBy('code')
                ->get()
                ->map(fn (Department $department) => ['value' => $department->id, 'label' => $department->code.' — '.$department->name])
                ->all(),
            FormField::TYPE_RISK_SELECTOR => IdentifiedRisk::query()
                ->where('mission_id', $instance->mission_id)
                ->orderByDesc('id')
                ->limit(50)
                ->get()
                ->map(fn (IdentifiedRisk $risk) => ['value' => $risk->id, 'label' => $risk->title ?: ('Risque #'.$risk->id)])
                ->all(),
            default => $field['options'] ?? [],
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function persistFiles(
        Request $request,
        WorkflowInstance $instance,
        WorkflowStage $stage,
        array $field,
        ?User $actor = null,
        ?Entretien $entretien = null,
    ): array {
        if (! $request->hasFile($field['field_key'])) {
            return [];
        }

        $uploadedFiles = Arr::wrap($request->file($field['field_key']));
        $documents = [];

        foreach ($uploadedFiles as $uploadedFile) {
            if ($uploadedFile === null) {
                continue;
            }

            $disk = 'local';
            $path = $uploadedFile->store('workflow_forms/'.$instance->mission_id.'/'.$stage->code, $disk);

            $metadata = [
                'disk' => $disk,
                'path' => $path,
                'filename' => basename($path),
                'original_name' => $uploadedFile->getClientOriginalName(),
                'mime_type' => $uploadedFile->getClientMimeType(),
                'size' => $uploadedFile->getSize(),
            ];

            if (Schema::hasTable('mission_documents')) {
                $document = MissionDocument::query()->create([
                    'mission_id' => $instance->mission_id,
                    'service_id' => $entretien?->service_id,
                    'entretien_id' => $entretien?->id,
                    'uploaded_by' => $actor?->id,
                    'filename' => $metadata['filename'],
                    'original_name' => $metadata['original_name'],
                    'mime_type' => $metadata['mime_type'],
                    'disk' => $metadata['disk'],
                    'path' => $metadata['path'],
                    'size' => $metadata['size'],
                    'category' => 'workflow_form',
                    'description' => $field['label'],
                    'version' => 1,
                    'metadata' => [
                        'workflow_stage_id' => $stage->id,
                        'workflow_stage_code' => $stage->code,
                    ],
                ]);

                $metadata['mission_document_id'] = $document->id;
            }

            $documents[] = $metadata;
        }

        return $documents;
    }

    private function normalizeSelectorValue(array $field, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $multiple = (bool) data_get($field, 'configuration.multiple', in_array($field['field_type'], [
            FormField::TYPE_MULTISELECT,
            FormField::TYPE_CHECKBOX,
            FormField::TYPE_RISK_SELECTOR,
        ], true));

        return $multiple ? array_values(Arr::wrap($value)) : $value;
    }
}
