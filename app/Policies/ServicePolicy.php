<?php

namespace App\Policies;

use App\Models\Mission;
use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Service $service): bool
    {
        return Mission::query()->whereKey($service->mission_id)->visibleToUser($user)->exists();
    }

    /** Création d’un service audité sur une mission (gouvernance institutionnelle). */
    public function create(User $user, Mission $mission): bool
    {
        return app(MissionPolicy::class)->manageServices($user, $mission);
    }

    public function update(User $user, Service $service): bool
    {
        $service->loadMissing('mission');

        return $service->mission !== null
            && app(MissionPolicy::class)->manageServices($user, $service->mission);
    }

    public function delete(User $user, Service $service): bool
    {
        return $this->update($user, $service);
    }

    /** Porte-documents, observations terrain : équipe mission ou gouvernance. */
    public function contribute(User $user, Service $service): bool
    {
        $service->loadMissing('mission');
        if ($service->mission === null) {
            return false;
        }

        $mp = app(MissionPolicy::class);

        return $mp->governMission($user, $service->mission)
            || $mp->updateMissionContent($user, $service->mission);
    }
}
