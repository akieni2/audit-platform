<?php

namespace App\Http\Requests\Missions;

use App\Models\Mission;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMissionRequest extends FormRequest
{
    /**
     * @return list<string>
     */
    public static function deadlineKeys(): array
    {
        return ['date_debut', 'date_fin', 'periode_audit', 'deadline'];
    }

    /**
     * @return list<string>
     */
    public static function departmentKeys(): array
    {
        return ['department_id', 'supervising_department_id'];
    }

    /**
     * @return list<string>
     */
    public static function governanceContentKeys(): array
    {
        return [
            'organisation',
            'reference',
            'objet',
            'description',
            'ordre_mission_reference',
            'date_ordre_mission',
            'observations_generales',
        ];
    }

    /**
     * @return list<string>
     */
    public static function operationalContentKeys(): array
    {
        return ['description', 'objet', 'observations_generales'];
    }

    public function authorize(): bool
    {
        $mission = $this->route('mission');
        $user = $this->user();

        return $mission instanceof Mission
            && $user !== null
            && ($user->can('governMission', $mission) || $user->can('updateMissionContent', $mission));
    }

    public function rules(): array
    {
        $mission = $this->route('mission');
        $user = $this->user();
        if (! $mission instanceof Mission || $user === null) {
            return [];
        }

        $operational = [
            'description' => ['nullable', 'string'],
            'objet' => ['nullable', 'string'],
            'observations_generales' => ['nullable', 'string'],
        ];

        if ($user->can('governMission', $mission)) {
            return array_merge([
                'organisation' => ['required', 'string', 'max:255'],
                'reference' => ['nullable', 'string', 'max:128'],
                'periode_audit' => ['nullable', 'string', 'max:255'],
                'ordre_mission_reference' => ['nullable', 'string', 'max:128'],
                'date_ordre_mission' => ['nullable', 'date'],
                'date_debut' => ['required', 'date'],
                'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
                'deadline' => ['nullable', 'date'],
                'department_id' => ['nullable', 'integer', 'exists:departments,id'],
                'supervising_department_id' => ['nullable', 'integer', 'exists:departments,id'],
                'treasury_entity_id' => ['nullable', 'integer', 'exists:treasury_entities,id'],
                'treasury_service_id' => ['nullable', 'integer', 'exists:treasury_services,id'],
                'audit_domain_id' => ['nullable', 'integer', 'exists:audit_domains,id'],
                'audit_template_id' => ['nullable', 'integer', 'exists:audit_templates,id'],
            ], $operational);
        }

        return $operational;
    }
}
