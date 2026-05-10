<?php

namespace App\Http\Requests\Admin;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dept = $this->route('department');

        return $dept instanceof Department
            && ($this->user()?->can('update', $dept) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $department = $this->route('department');
        $id = $department instanceof Department ? $department->id : 0;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32', Rule::unique('departments', 'code')->ignore($id)],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'max:64'],
            'active' => ['sometimes', 'boolean'],
            'supervisor_user_id' => ['nullable', 'exists:users,id'],
            'accent_color' => ['nullable', 'string', 'max:32'],
            'logo_path' => ['nullable', 'string', 'max:512'],
        ];
    }
}
