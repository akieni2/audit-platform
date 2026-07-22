<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
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
            'password' => 'mot de passe',
            'password_confirmation' => 'confirmation du mot de passe',
        ];
    }

    /**
     * Messages explicites pour la politique DGCPT (évitent les libellés génériques en anglais).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'La confirmation ne correspond pas au mot de passe saisi.',
            'password.min' => 'Le mot de passe doit comporter au moins :min caractères.',
            'password.mixed' => 'Le mot de passe doit contenir au moins une lettre majuscule et une lettre minuscule.',
            'password.numbers' => 'Le mot de passe doit contenir au moins un chiffre.',
            'password.symbols' => 'Le mot de passe doit contenir au moins un caractère spécial (symbole ou ponctuation, par exemple ! ? @ #).',
            'password.uncompromised' => 'Ce mot de passe est trop courant ou figure dans des fuites de données connues. Choisissez une combinaison différente et plus longue.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $actor = $this->user();

        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults(), 'confirmed'],
            'department_id' => ['required', Rule::in($actor?->managedDepartmentIds() ?? [])],
            'role_id' => ['required', Rule::exists('roles', 'id')->where(function ($query) use ($actor) {
                if ($actor !== null && ! $actor->canSuperviseAllDepartments()) {
                    $query->where('hierarchy_level', '<', (int) ($actor->institutionalRole?->hierarchy_level ?? 0));
                }
            })],
            'position' => ['nullable', 'string', 'max:255'],
            'matricule' => ['nullable', 'string', 'max:64'],
            'telephone' => ['nullable', 'string', 'max:32'],
            'intercom' => ['nullable', 'string', 'max:64'],
        ];
    }
}
