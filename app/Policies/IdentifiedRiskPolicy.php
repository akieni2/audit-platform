<?php

namespace App\Policies;

use App\Models\IdentifiedRisk;
use App\Models\User;

class IdentifiedRiskPolicy
{
    public function validateHuman(User $user, IdentifiedRisk $risk): bool
    {
        $risk->loadMissing('mission');

        return $risk->mission !== null
            && app(MissionPolicy::class)->governMission($user, $risk->mission);
    }

    public function promote(User $user, IdentifiedRisk $risk): bool
    {
        return $this->validateHuman($user, $risk);
    }
}
