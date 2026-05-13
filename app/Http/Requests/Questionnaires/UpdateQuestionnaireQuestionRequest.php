<?php

namespace App\Http\Requests\Questionnaires;

use App\Models\QuestionnaireQuestion;

class UpdateQuestionnaireQuestionRequest extends StoreQuestionnaireQuestionRequest
{
    public function authorize(): bool
    {
        $question = $this->route('question');

        return $question instanceof QuestionnaireQuestion
            && $question->section?->template !== null
            && ($this->user()?->can('update', $question->section->template) ?? false);
    }
}
