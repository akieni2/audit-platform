<?php

namespace App\Http\Requests\Workflows;

use App\Models\WorkflowTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkflowTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $slug = (string) $this->input('slug', '');
        if ($slug === '' && filled($this->input('name'))) {
            $slug = \Illuminate\Support\Str::slug((string) $this->input('name'));
        }

        $this->merge([
            'slug' => $slug,
            'department_id' => filled($this->input('department_id')) ? (int) $this->input('department_id') : null,
            'code' => filled($this->input('code')) ? strtoupper(trim((string) $this->input('code'))) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var WorkflowTemplate|null $template */
        $template = $this->route('template');

        return [
            'department_id' => ['nullable', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:120',
                'alpha_dash',
                Rule::unique('workflow_templates', 'slug')->ignore($template?->id),
            ],
            'description' => ['nullable', 'string'],
            'code' => ['nullable', 'string', 'max:64'],
        ];
    }
}
