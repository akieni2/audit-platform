<?php

namespace App\Http\Requests\Services;

use App\Models\Mission;
use App\Policies\MissionPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMissionServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mission = $this->route('mission');

        return $mission instanceof Mission
            && $this->user() !== null
            && app(MissionPolicy::class)->manageServices($this->user(), $mission);
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:64'],
            'nom' => ['required', 'string', 'max:255'],
            'responsable' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'chef_service_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'chef_service_nom' => ['nullable', 'string', 'max:255'],
            'chef_service_fonction' => ['nullable', 'string', 'max:255'],
            'chef_service_email' => ['nullable', 'email', 'max:255'],
            'chef_service_telephone' => ['nullable', 'string', 'max:64'],
            'service_type' => ['nullable', 'string', 'max:64'],
            'service_scope' => ['nullable', 'string', 'max:255'],
            'active' => ['sometimes', 'boolean'],
            'observations' => ['nullable', 'string'],
            'audit_priority' => ['nullable', 'string', 'max:32'],
            'risk_level' => ['nullable', 'string', 'max:32'],
            'audit_status' => ['nullable', 'string', 'max:32', Rule::in(['pending', 'in_audit', 'audited', 'closed'])],
            'metadata' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'active' => $this->boolean('active'),
        ]);
    }
}
