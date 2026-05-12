<?php

namespace App\Http\Requests\Missions;

use App\Models\Mission;
use App\Models\MissionTeamMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMissionTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mission = $this->route('mission');
        $user = $this->user();

        return $mission instanceof Mission
            && $user !== null
            && $user->can('update', $mission);
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
            'mission_role' => [
                'required',
                'string',
                Rule::in(MissionTeamMember::missionRoles()),
            ],
            'designation' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $mission = $this->route('mission');
            $actor = $this->user();
            if (! $mission instanceof Mission || $actor === null) {
                return;
            }

            $userId = (int) $this->input('user_id');
            if (MissionTeamMember::query()
                ->where('mission_id', $mission->id)
                ->where('user_id', $userId)
                ->exists()) {
                $validator->errors()->add('user_id', 'Cet utilisateur est déjà membre de l’équipe.');
            }

            $eligible = $mission->eligibleTeamUsers($actor)->pluck('id');
            if ($actor->canSuperviseAllDepartments()) {
                return;
            }

            if (! $eligible->contains($userId)) {
                $validator->errors()->add(
                    'user_id',
                    'L’utilisateur choisi n’est pas dans le périmètre institutionnel autorisé pour cette mission.'
                );
            }
        });
    }
}
