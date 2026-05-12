<?php

namespace App\Policies;

use App\Models\Mission;
use App\Models\MissionDocument;
use App\Models\User;

class MissionDocumentPolicy
{
    public function view(User $user, MissionDocument $document): bool
    {
        return Mission::query()->whereKey($document->mission_id)->visibleToUser($user)->exists();
    }

    public function create(User $user, Mission $mission): bool
    {
        $mp = app(MissionPolicy::class);

        return $mp->governMission($user, $mission)
            || $mp->updateMissionContent($user, $mission);
    }

    public function delete(User $user, MissionDocument $document): bool
    {
        $document->loadMissing('mission');
        if ($document->mission === null) {
            return false;
        }

        $mp = app(MissionPolicy::class);

        return $mp->governMission($user, $document->mission)
            || ($mp->updateMissionContent($user, $document->mission) && (int) $document->uploaded_by === (int) $user->id);
    }
}
