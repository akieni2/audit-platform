<?php

namespace App\Http\Requests\Admin;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Department::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32', Rule::unique('departments', 'code')],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'max:64'],
            'active' => ['sometimes', 'boolean'],
            'supervisor_user_id' => ['nullable', 'exists:users,id'],
            'accent_color' => ['nullable', 'string', 'max:32'],
            'logo_path' => ['nullable', 'string', 'max:512'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
