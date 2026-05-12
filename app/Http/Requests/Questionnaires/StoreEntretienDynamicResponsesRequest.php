<?php

namespace App\Http\Requests\Questionnaires;

use App\Models\Entretien;
use Illuminate\Foundation\Http\FormRequest;

class StoreEntretienDynamicResponsesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $e = $this->route('entretien');

        return $e instanceof Entretien
            && $this->user()?->can('conductQuestionnaire', $e);
    }

    protected function prepareForValidation(): void
    {
        $rows = $this->input('responses', []);
        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            if (isset($row['answer_tri']) && is_string($row['answer_tri'])) {
                $row['answer_boolean'] = match ($row['answer_tri']) {
                    'yes' => true,
                    'no' => false,
                    'na' => null,
                    default => null,
                };
                unset($row['answer_tri']);
                $rows[$i] = $row;
            }
        }

        $this->merge(['responses' => $rows]);
    }

    public function rules(): array
    {
        return [
            'responses' => ['required', 'array', 'min:1'],
            'responses.*.questionnaire_question_id' => ['required', 'integer', 'exists:questionnaire_questions,id'],
            'responses.*.answer_tri' => ['nullable', 'string', 'in:yes,no,na'],
            'responses.*.answer_boolean' => ['nullable', 'boolean'],
            'responses.*.answer_text' => ['nullable', 'string'],
            'responses.*.answer_json' => ['nullable', 'array'],
            'responses.*.observation' => ['nullable', 'string'],
            'responses.*.uploaded_documents_metadata' => ['nullable', 'array'],
            'responses.*.detected_risk' => ['nullable', 'string'],
            'responses.*.identified_risk' => ['nullable', 'array'],
            'responses.*.identified_risk.title' => ['nullable', 'string', 'max:500'],
            'responses.*.identified_risk.description' => ['nullable', 'string'],
            'responses.*.identified_risk.category' => ['nullable', 'string', 'max:255'],
            'responses.*.identified_risk.probability' => ['nullable', 'string', 'max:64'],
            'responses.*.identified_risk.impact' => ['nullable', 'string', 'max:64'],
            'responses.*.identified_risk.criticality' => ['nullable', 'string', 'max:64'],
            'responses.*.identified_risk.recommendation' => ['nullable', 'string'],
        ];
    }
}
