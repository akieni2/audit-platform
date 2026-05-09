<?php

namespace App\Http\Requests;

use App\Domain\Risk\Enums\RiskStatus;
use App\Models\Risque;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRisqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Risque $risque */
        $risque = $this->route('risque');

        return $risque !== null && $this->user()->can('update', $risque);
    }

    public function rules(): array
    {
        return [
            'description' => ['sometimes', 'required', 'string', 'max:2000'],
            'impact_inherent' => ['sometimes', 'required', 'integer', 'min:1', 'max:5'],
            'probabilite_inherent' => ['sometimes', 'required', 'integer', 'min:1', 'max:5'],
            'proprietaire' => ['nullable', 'string', 'max:255'],
            'departement' => ['nullable', 'string', 'max:255'],
            'date_revue' => ['nullable', 'date'],
            'plan_mitigation' => ['nullable', 'string', 'max:10000'],
            'statut_risque' => ['nullable', 'string', Rule::in(RiskStatus::values())],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('description')) {
            $merge['description'] = strip_tags((string) $this->input('description'));
        }
        if ($this->has('plan_mitigation')) {
            $merge['plan_mitigation'] = $this->input('plan_mitigation')
                ? strip_tags((string) $this->input('plan_mitigation'))
                : null;
        }
        if ($this->has('proprietaire')) {
            $merge['proprietaire'] = $this->input('proprietaire')
                ? strip_tags((string) $this->input('proprietaire'))
                : null;
        }
        if ($this->has('departement')) {
            $merge['departement'] = $this->input('departement')
                ? strip_tags((string) $this->input('departement'))
                : null;
        }
        $this->merge($merge);
    }
}
