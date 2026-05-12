<?php

namespace App\Policies;

use App\Models\Entretien;
use App\Models\User;

class EntretienPolicy
{
    public function view(User $user, Entretien $entretien): bool
    {
        return Entretien::query()->whereKey($entretien->id)->visibleToUser($user)->exists();
    }

    /** Conduite du questionnaire dynamique (équipe mission / gouvernance). */
    public function conductQuestionnaire(User $user, Entretien $entretien): bool
    {
        if (! $this->view($user, $entretien)) {
            return false;
        }

        $entretien->loadMissing('mission');
        $mission = $entretien->mission;
        if ($mission === null) {
            return false;
        }

        return app(\App\Policies\MissionPolicy::class)->governMission($user, $mission)
            || app(\App\Policies\MissionPolicy::class)->updateMissionContent($user, $mission);
    }

    /** Rattacher un modèle de questionnaire à l’entretien (gouvernance mission). */
    public function attachTemplate(User $user, Entretien $entretien): bool
    {
        if (! $this->view($user, $entretien)) {
            return false;
        }

        $entretien->loadMissing('mission');
        $mission = $entretien->mission;

        return $mission !== null && app(\App\Policies\MissionPolicy::class)->governMission($user, $mission);
    }
}
