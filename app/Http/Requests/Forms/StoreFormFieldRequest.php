<?php

namespace App\Http\Requests\Forms;

use App\Models\FormField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreFormFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $fieldKey = (string) $this->input('field_key', '');
        if ($fieldKey === '' && filled($this->input('label'))) {
            $fieldKey = Str::upper(Str::slug((string) $this->input('label'), '_'));
        }

        $this->merge([
            'field_key' => $fieldKey,
            'sort_order' => filled($this->input('sort_order')) ? (int) $this->input('sort_order') : 0,
            'is_required' => $this->boolean('is_required'),
            'is_repeatable' => $this->boolean('is_repeatable'),
            'active' => $this->boolean('active', true),
            'default_value' => $this->normalizeDefaultValue(),
            'configuration_json' => $this->decodeJsonField('configuration_json_text'),
            'validation_rules_json' => $this->decodeJsonField('validation_rules_json_text'),
            'conditional_rules_json' => $this->decodeJsonField('conditional_rules_json_text'),
            'options_payload' => $this->parseOptions(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'field_key' => ['required', 'string', 'max:120'],
            'label' => ['required', 'string', 'max:255'],
            'help_text' => ['nullable', 'string'],
            'field_type' => ['required', Rule::in(FormField::fieldTypes())],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'default_value' => ['nullable'],
            'configuration_json' => ['nullable', 'array'],
            'validation_rules_json' => ['nullable', 'array'],
            'conditional_rules_json' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_required' => ['nullable', 'boolean'],
            'is_repeatable' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
            'options_payload' => ['nullable', 'array'],
            'options_payload.*.label' => ['required_with:options_payload', 'string', 'max:255'],
            'options_payload.*.value' => ['required_with:options_payload', 'string', 'max:255'],
            'options_payload.*.is_default' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return list<array{id?:int,label:string,value:string,is_default:bool}>
     */
    protected function parseOptions(): array
    {
        $raw = preg_split('/\r\n|\r|\n/', (string) $this->input('options_text', '')) ?: [];
        $options = [];

        foreach ($raw as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line, 2));
            $label = $parts[0] ?? '';
            $value = $parts[1] ?? $label;

            $options[] = [
                'label' => $label,
                'value' => $value,
                'is_default' => false,
            ];
        }

        return $options;
    }

    protected function normalizeDefaultValue(): mixed
    {
        $value = $this->input('default_value_text');

        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $decoded = json_decode((string) $value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $value;
        }

        return is_array($decoded)
            ? json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : $decoded;
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
