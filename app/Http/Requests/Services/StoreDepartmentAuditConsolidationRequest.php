<?php

namespace App\Http\Requests\Services;

use App\Models\Mission;
use App\Policies\DepartmentAuditConsolidationPolicy;
use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentAuditConsolidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mission = $this->route('mission');

        return $mission instanceof Mission
            && $this->user() !== null
            && app(DepartmentAuditConsolidationPolicy::class)->create($this->user(), $mission);
    }

    public function rules(): array
    {
        return [
            'synthesis' => ['nullable', 'string'],
            'global_risk_level' => ['nullable', 'string', 'max:64'],
            'key_findings' => ['nullable', 'string'],
            'recommendations' => ['nullable', 'string'],
        ];
    }
}
