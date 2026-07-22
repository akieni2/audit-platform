<?php

namespace App\Http\Requests\Missions;

use App\Models\Mission;
use Illuminate\Foundation\Http\FormRequest;

class StoreMissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Mission::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'creation_token' => ['nullable', 'uuid'],
            'organisation' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:128'],
            'objet' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'periode_audit' => ['nullable', 'string', 'max:255'],
            'ordre_mission_reference' => ['nullable', 'string', 'max:128'],
            'date_ordre_mission' => ['nullable', 'date'],
            'observations_generales' => ['nullable', 'string'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'deadline' => ['nullable', 'date'],
        ];
    }
}
