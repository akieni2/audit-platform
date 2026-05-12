<?php

namespace App\Http\Requests\Questionnaires;

use App\Models\QuestionnaireTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuestionnaireTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $t = $this->route('questionnaire_template');

        return $t instanceof QuestionnaireTemplate
            && $this->user()?->can('update', $t);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'active' => $this->boolean('active'),
        ]);
    }

    public function rules(): array
    {
        $t = $this->route('questionnaire_template');
        $id = $t instanceof QuestionnaireTemplate ? $t->id : 0;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:128', Rule::unique('questionnaire_templates', 'slug')->ignore($id)],
            'description' => ['nullable', 'string'],
            'mission_type' => ['nullable', 'string', 'max:64'],
            'department_scope' => ['nullable', 'array'],
            'department_scope.*' => ['integer', 'exists:departments,id'],
            'active' => ['sometimes', 'boolean'],
            'version' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
