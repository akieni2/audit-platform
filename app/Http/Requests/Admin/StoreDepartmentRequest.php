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
            'parent_department_id' => ['nullable', 'exists:departments,id'],
            'governance_scope' => ['nullable', 'string', 'max:64'],
            'default_methodology_template_id' => ['nullable', 'exists:methodology_templates,id'],
            'default_taxonomy_id' => ['nullable', 'exists:taxonomies,id'],
            'executive_visibility' => ['sometimes', 'boolean'],
            'supervisor_user_id' => ['nullable', 'exists:users,id'],
            'position_title' => ['nullable', 'string', 'max:255'],
            'position_description' => ['nullable', 'string'],
            'position_activities' => ['nullable', 'string'],
            'create_top_manager' => ['sometimes', 'boolean'],
            'top_manager_title' => ['nullable', 'required_if:create_top_manager,1', 'string', 'max:255'],
            'top_manager_name' => ['nullable', 'string', 'max:255'],
            'top_manager_email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'top_manager_role_id' => ['nullable', 'exists:roles,id'],
            'accent_color' => ['nullable', 'string', 'max:32'],
            'logo_path' => ['nullable', 'string', 'max:512'],
        ];
    }
}
