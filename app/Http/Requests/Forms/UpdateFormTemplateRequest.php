<?php

namespace App\Http\Requests\Forms;

use App\Models\FormTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateFormTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $slug = (string) $this->input('slug', '');
        if ($slug === '' && filled($this->input('name'))) {
            $slug = Str::slug((string) $this->input('name'));
        }

        $this->merge([
            'slug' => $slug,
            'department_scope' => collect($this->input('department_scope', []))
                ->filter(fn ($value) => filled($value))
                ->map(fn ($value) => (int) $value)
                ->values()
                ->all(),
            'component_key' => filled($this->input('component_key'))
                ? trim((string) $this->input('component_key'))
                : 'dynamic_form',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var FormTemplate|null $template */
        $template = $this->route('template');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('form_templates', 'slug')->ignore($template?->id)],
            'description' => ['nullable', 'string'],
            'component_key' => ['nullable', 'string', 'max:80'],
            'department_scope' => ['nullable', 'array'],
            'department_scope.*' => ['integer', 'exists:departments,id'],
        ];
    }
}
