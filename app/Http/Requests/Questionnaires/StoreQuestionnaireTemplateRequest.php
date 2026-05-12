<?php

namespace App\Http\Requests\Questionnaires;

use App\Models\QuestionnaireTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreQuestionnaireTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', QuestionnaireTemplate::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:128', Rule::unique('questionnaire_templates', 'slug')],
            'description' => ['nullable', 'string'],
            'mission_type' => ['nullable', 'string', 'max:64'],
            'department_scope' => ['nullable', 'array'],
            'department_scope.*' => ['integer', 'exists:departments,id'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('name')) {
            $this->merge(['slug' => Str::slug($this->input('name')).'-'.Str::lower(Str::random(4))]);
        }

        $this->merge([
            'active' => $this->boolean('active'),
        ]);
    }
}
