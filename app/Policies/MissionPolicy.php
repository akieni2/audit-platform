<?php

namespace App\Policies;

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

    public function create(User $user): bool
    {
        return $user->department_id !== null || $user->canManageMissions();
    }

    public function update(User $user, Mission $mission): bool
    {
        return app(MissionWorkflowService::class)->canEditMissionBasics($user, $mission);
    }

    public function transition(User $user, Mission $mission, string $action): bool
    {
        return app(MissionWorkflowService::class)->can($user, $mission, $action);
    }
}
