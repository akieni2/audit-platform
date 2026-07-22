<?php

namespace App\Http\Requests\Questionnaires;

use App\Models\QuestionnaireTemplate;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionnaireSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $t = $this->route('template') ?? $this->route('questionnaire_template');

        return $t instanceof QuestionnaireTemplate
            && $this->user()?->can('update', $t);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'section_type' => ['sometimes', 'required', 'string', 'in:theme,thematic,subtheme'],
            'parent_section_id' => ['nullable', 'integer', 'exists:questionnaire_sections,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
