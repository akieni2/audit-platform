<?php

namespace App\Http\Requests\Admin;

use App\Models\Department;
use App\Support\OrganizationStructure;
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

        if (! $this->user()?->canAdministerOrganization()) {
            return [
                'default_methodology_template_id' => ['required', Rule::exists('methodology_templates', 'id')->where('active', true)],
                'default_taxonomy_id' => ['nullable', 'exists:taxonomies,id'],
            ];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32', Rule::unique('departments', 'code')->ignore($id)],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', Rule::in(array_keys(OrganizationStructure::typeOptions()))],
            'active' => ['sometimes', 'boolean'],
            'parent_department_id' => ['nullable', 'exists:departments,id', Rule::notIn([$id])],
            'governance_scope' => ['nullable', 'string', 'max:64'],
            'default_methodology_template_id' => ['nullable', Rule::exists('methodology_templates', 'id')->where('active', true)],
            'default_taxonomy_id' => ['nullable', 'exists:taxonomies,id'],
            'executive_visibility' => ['sometimes', 'boolean'],
            'supervisor_user_id' => ['nullable', 'exists:users,id'],
            'position_title' => ['nullable', 'string', 'max:255'],
            'position_description' => ['nullable', 'string'],
            'position_activities' => ['nullable', 'string'],
            'create_top_manager' => ['sometimes', 'boolean'],
            'top_manager_title' => ['nullable', 'required_if:create_top_manager,1', 'string', 'max:255'],
            'top_manager_name' => ['nullable', 'string', 'max:255'],
            'top_manager_email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'top_manager_role_id' => ['nullable', 'exists:roles,id'],
            'accent_color' => ['nullable', 'string', 'max:32'],
            'logo_path' => ['nullable', 'string', 'max:512'],
        ];
    }

    public function withValidator($validator): void
    {
        if (! $this->user()?->canAdministerOrganization()) {
            return;
        }

        $validator->after(function ($validator): void {
            $department = $this->route('department');
            $type = (string) $this->input('type');
            $parentId = $this->integer('parent_department_id') ?: null;

            if (OrganizationStructure::requiresParent($type) && $parentId === null) {
                $validator->errors()->add('parent_department_id', 'Cette structure doit être rattachée à une structure parente.');
                return;
            }


            if (OrganizationStructure::requiresAuditMethodology($type) && ! $this->filled('default_methodology_template_id')) {
                $validator->errors()->add('default_methodology_template_id', 'Un référentiel d’audit doit être choisi pour cette structure.');
            }

            if ($parentId !== null) {
                $parent = Department::query()->find($parentId);
                if ($parent !== null && ! in_array($parent->type, OrganizationStructure::allowedParentTypes($type), true)) {
                    $validator->errors()->add('parent_department_id', 'Le type de structure parente est incompatible avec ce niveau hiérarchique.');
                }

                if ($department instanceof Department && $parent?->isDescendantOf($department)) {
                    $validator->errors()->add('parent_department_id', 'Une structure ne peut pas être rattachée à l’une de ses propres sous-structures.');
                }
            }
        });
    }
}
