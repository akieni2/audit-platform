<?php

namespace App\Http\Requests\Questionnaires;

use App\Domain\Risk\Enums\CriticalityLevel;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionnaireQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $t = $this->resolveTemplate();

        return $t instanceof QuestionnaireTemplate
            && $this->user()?->can('update', $t);
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:64'],
            'question' => ['required', 'string'],
            'help_text' => ['nullable', 'string'],
            'question_type' => ['required', 'string', Rule::in(QuestionnaireQuestion::questionTypes())],
            'required' => ['sometimes', 'boolean'],
            'allows_observation' => ['sometimes', 'boolean'],
            'allows_risk_detection' => ['sometimes', 'boolean'],
            'expected_documents' => ['nullable', 'string'],
            'risk_category' => ['nullable', 'string', 'max:128'],
            'risk_level' => ['nullable', 'string', Rule::in(array_keys(CriticalityLevel::options()))],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'array'],
            'metadata.options' => [
                'nullable',
                'array',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (
                        in_array($this->input('question_type'), [
                            QuestionnaireQuestion::TYPE_SELECT,
                            QuestionnaireQuestion::TYPE_RADIO,
                            QuestionnaireQuestion::TYPE_CHECKBOX,
                        ], true)
                        && empty(array_filter(is_array($value) ? $value : []))
                    ) {
                        $fail('Les questions à choix doivent définir au moins une option.');
                    }
                },
            ],
            'metadata.options.*' => ['nullable', 'string', 'max:255'],
            'metadata.scoring' => ['nullable', 'array'],
            'metadata.scoring.enabled' => ['nullable', 'boolean'],
            'metadata.scoring.weight' => ['nullable', 'integer', 'min:0', 'max:100'],
            'metadata.risk_mapping' => ['nullable', 'array'],
            'metadata.risk_mapping.enabled' => ['nullable', 'boolean'],
            'metadata.risk_mapping.category' => ['nullable', 'string', 'max:128'],
            'metadata.risk_mapping.default_criticality' => ['nullable', 'string', Rule::in(array_keys(CriticalityLevel::options()))],
            'metadata.documents' => ['nullable', 'array'],
            'metadata.documents.required' => ['nullable', 'boolean'],
            'metadata.documents.list' => ['nullable', 'array'],
            'metadata.documents.list.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $decodedMetadata = $this->input('metadata');
        if (is_string($this->input('metadata_json')) && trim((string) $this->input('metadata_json')) !== '') {
            $decoded = json_decode((string) $this->input('metadata_json'), true);
            if (is_array($decoded)) {
                $decodedMetadata = $decoded;
            }
        }

        $criticality = CriticalityLevel::fromMixed((string) ($this->input('risk_level') ?: data_get($decodedMetadata, 'risk_mapping.default_criticality')));
        $options = $this->normalizeLines((string) $this->input('options_text', ''));
        $documentList = $this->normalizeLines((string) $this->input('documents_list_text', ''));

        if ($options === [] && is_array(data_get($decodedMetadata, 'options'))) {
            $options = array_values(array_filter(data_get($decodedMetadata, 'options', []), fn ($value) => filled($value)));
        }

        if ($documentList === [] && is_array(data_get($decodedMetadata, 'documents.list'))) {
            $documentList = array_values(array_filter(data_get($decodedMetadata, 'documents.list', []), fn ($value) => filled($value)));
        }

        $riskCategory = $this->input('risk_category') ?: data_get($decodedMetadata, 'risk_mapping.category');
        $allowsRiskDetection = $this->has('allows_risk_detection')
            ? $this->boolean('allows_risk_detection')
            : (bool) data_get($decodedMetadata, 'risk_mapping.enabled', false);

        if ($this->input('question_type') === QuestionnaireQuestion::TYPE_RISK_CAPTURE) {
            $allowsRiskDetection = true;
        }

        $metadata = [
            'options' => $options,
            'scoring' => [
                'enabled' => $this->has('scoring_enabled')
                    ? $this->boolean('scoring_enabled')
                    : (bool) data_get($decodedMetadata, 'scoring.enabled', false),
                'weight' => (int) ($this->input('scoring_weight', data_get($decodedMetadata, 'scoring.weight', 0)) ?: 0),
            ],
            'risk_mapping' => [
                'enabled' => $this->has('risk_mapping_enabled')
                    ? $this->boolean('risk_mapping_enabled')
                    : $allowsRiskDetection,
                'category' => $riskCategory,
                'default_criticality' => $criticality?->value,
            ],
            'documents' => [
                'required' => $this->has('documents_required')
                    ? $this->boolean('documents_required')
                    : (bool) data_get($decodedMetadata, 'documents.required', false),
                'list' => $documentList,
            ],
        ];

        $this->merge([
            'required' => $this->boolean('required'),
            'allows_observation' => $this->boolean('allows_observation'),
            'allows_risk_detection' => $allowsRiskDetection,
            'active' => $this->has('active') ? $this->boolean('active') : true,
            'sort_order' => (int) ($this->input('sort_order') ?: 0),
            'risk_category' => $riskCategory,
            'risk_level' => $criticality?->value,
            'expected_documents' => $documentList !== [] ? implode("\n", $documentList) : $this->input('expected_documents'),
            'metadata' => $metadata,
        ]);
    }

    protected function resolveTemplate(): ?QuestionnaireTemplate
    {
        $template = $this->route('template') ?? $this->route('questionnaire_template');
        if ($template instanceof QuestionnaireTemplate) {
            return $template;
        }

        $section = $this->route('section');
        if ($section instanceof \App\Models\QuestionnaireSection) {
            return $section->template;
        }

        $question = $this->route('question');
        if ($question instanceof \App\Models\QuestionnaireQuestion) {
            return $question->section?->template;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function normalizeLines(string $value): array
    {
        return array_values(array_filter(array_map(
            static fn (string $line) => trim($line),
            preg_split('/\r\n|\r|\n|,/', $value) ?: []
        )));
    }
}
