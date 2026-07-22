<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\Mission;
use App\Models\User;
use App\Services\Missions\MissionWorkflowService;

class MissionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Mission $mission): bool
    {
        return Mission::query()->whereKey($mission->id)->visibleToUser($user)->exists();
    }

    /**
     * Création : superviseur du département, inspection nationale (dont super_admin / supervise_global).
     */
    public function create(User $user): bool
    {
        $user->loadMissing('institutionalRole');

        if ($user->canSuperviseAllDepartments()) {
            return true;
        }

        if ($user->department_id === null) {
            return false;
        }

        $department = Department::query()->find($user->department_id);

        if ($department === null || ! in_array($department->type, [
            'direction_generale',
            'administration',
            'direction',
            'department',
            'departement',
            'inspection_services',
            'sous_direction',
            'pole',
        ], true)) {
            return false;
        }

        return $user->isDepartmentSupervisorOf((int) $department->id);
    }

    /**
     * Mise à jour générique (gouvernance + contenu opérationnel) — utilisé par les Gates Laravel standards.
     */
    public function update(User $user, Mission $mission): bool
    {
        return $this->governMission($user, $mission) || $this->updateMissionContent($user, $mission);
    }

    /**
     * Formulaires mission (champs visibles selon profil).
     */
    public function editMission(User $user, Mission $mission): bool
    {
        return $this->governMission($user, $mission) || $this->updateMissionContent($user, $mission);
    }

    /**
     * Propriétaire institutionnel ou inspection nationale.
     */
    public function governMission(User $user, Mission $mission): bool
    {
        if (! Mission::query()->whereKey($mission->id)->visibleToUser($user)->exists()) {
            return false;
        }

        return $user->canGovernMissionInstitutionally($mission);
    }

    /**
     * Structuration des services audités (Phase 2) — superviseur / inspection nationale.
     */
    public function manageServices(User $user, Mission $mission): bool
    {
        return $this->governMission($user, $mission);
    }

    public function assignTeamMembers(User $user, Mission $mission): bool
    {
        return $this->governMission($user, $mission);
    }

    /** Une mission non démarrée peut être archivée par son responsable d'unité. */
    public function delete(User $user, Mission $mission): bool
    {
        return $mission->mission_status === Mission::STATUS_BROUILLON
            && $this->governMission($user, $mission);
    }

    public function updateDeadlines(User $user, Mission $mission): bool
    {
        return $this->governMission($user, $mission);
    }

    /**
     * Clôture / démarrage institutionnel (complété par le moteur workflow existant).
     */
    public function closeMission(User $user, Mission $mission): bool
    {
        return $this->governMission($user, $mission);
    }

    /**
     * Contenu non stratégique : chef de mission, IV, IVA, agent (rôle missionnel, pas IAM).
     */
    public function updateMissionContent(User $user, Mission $mission): bool
    {
        if (! Mission::query()->whereKey($mission->id)->visibleToUser($user)->exists()) {
            return false;
        }

        if ($this->governMission($user, $mission)) {
            return true;
        }

        return $user->isMissionOperationalContributor($mission);
    }

    public function transition(User $user, Mission $mission, string $action): bool
    {
        if (in_array($action, [
            MissionWorkflowService::ACTION_DEMARRER,
            MissionWorkflowService::ACTION_CLOTURER,
        ], true)) {
            if (! $this->closeMission($user, $mission)) {
                return false;
            }
        }

        return app(MissionWorkflowService::class)->can($user, $mission, $action);
    }
}
