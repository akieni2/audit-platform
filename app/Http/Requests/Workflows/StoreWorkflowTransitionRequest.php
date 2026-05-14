<?php

namespace App\Http\Requests\Workflows;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkflowTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'from_stage_id' => (int) $this->input('from_stage_id'),
            'to_stage_id' => (int) $this->input('to_stage_id'),
            'is_automatic' => $this->boolean('is_automatic'),
            'condition_configuration' => $this->decodeJsonField('condition_configuration_text'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from_stage_id' => ['required', 'integer', 'exists:workflow_stages,id', 'different:to_stage_id'],
            'to_stage_id' => ['required', 'integer', 'exists:workflow_stages,id'],
            'condition_type' => ['nullable', 'string', 'max:64'],
            'condition_configuration' => ['nullable', 'array'],
            'role_required' => ['nullable', 'string', 'max:120'],
            'is_automatic' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function decodeJsonField(string $key): ?array
    {
        $value = trim((string) $this->input($key, ''));
        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
