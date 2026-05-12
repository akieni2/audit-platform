<?php

namespace App\Policies;

use App\Models\DepartmentAuditConsolidation;
use App\Models\Mission;
use App\Models\User;

class DepartmentAuditConsolidationPolicy
{
    public function view(User $user, DepartmentAuditConsolidation $row): bool
    {
        return Mission::query()->whereKey($row->mission_id)->visibleToUser($user)->exists();
    }

    public function create(User $user, Mission $mission): bool
    {
        return app(MissionPolicy::class)->governMission($user, $mission);
    }

    public function update(User $user, DepartmentAuditConsolidation $row): bool
    {
        $row->loadMissing('mission');

        return $row->mission !== null
            && app(MissionPolicy::class)->governMission($user, $row->mission);
    }
}
