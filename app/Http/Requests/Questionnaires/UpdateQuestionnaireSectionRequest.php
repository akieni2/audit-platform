<?php

namespace App\Http\Requests\Questionnaires;

use App\Models\QuestionnaireSection;

class UpdateQuestionnaireSectionRequest extends StoreQuestionnaireSectionRequest
{
    public function authorize(): bool
    {
        $section = $this->route('section');

        return $section instanceof QuestionnaireSection
            && $section->template !== null
            && ($this->user()?->can('update', $section->template) ?? false);
    }
}
