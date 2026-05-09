<?php

namespace App\Http\Requests;

use App\Domain\Risk\Enums\RiskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRisqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'actif_id' => ['required', 'integer', 'exists:actifs,id'],
            'description' => ['required', 'string', 'max:2000'],
            'impact_inherent' => ['required', 'integer', 'min:1', 'max:5'],
            'probabilite_inherent' => ['required', 'integer', 'min:1', 'max:5'],
            'proprietaire' => ['nullable', 'string', 'max:255'],
            'departement' => ['nullable', 'string', 'max:255'],
            'date_revue' => ['nullable', 'date'],
            'plan_mitigation' => ['nullable', 'string', 'max:10000'],
            'statut_risque' => ['nullable', 'string', Rule::in(RiskStatus::values())],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'description' => strip_tags((string) $this->input('description', '')),
            'plan_mitigation' => $this->input('plan_mitigation')
                ? strip_tags((string) $this->input('plan_mitigation'))
                : null,
            'proprietaire' => $this->input('proprietaire')
                ? strip_tags((string) $this->input('proprietaire'))
                : null,
            'departement' => $this->input('departement')
                ? strip_tags((string) $this->input('departement'))
                : null,
        ]);
    }
}
