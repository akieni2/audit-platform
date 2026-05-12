<?php

namespace App\Http\Requests\Questionnaires;

use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionnaireQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $t = $this->route('questionnaire_template');

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
            'risk_level' => ['nullable', 'string', 'max:32'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
