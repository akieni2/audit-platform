<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');
        if (! $user instanceof User) {
            return false;
        }

        return $this->user()?->can('update', $user) ?? false;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'department_id' => 'département',
            'nom' => 'nom',
            'prenom' => 'prénom',
            'role_id' => 'catégorie',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->route('user');
        $id = $user instanceof User ? $user->id : 0;

        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'role_id' => ['nullable', 'exists:roles,id'],
            'position' => ['nullable', 'string', 'max:255'],
            'matricule' => ['nullable', 'string', 'max:64'],
            'telephone' => ['nullable', 'string', 'max:32'],
            'intercom' => ['nullable', 'string', 'max:64'],
        ];
    }
}
