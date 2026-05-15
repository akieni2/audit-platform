<?php

namespace App\Services\Runtime;

use App\Models\Mission;
use App\Models\User;

class DistributedRuntimeDispatcher
{
    public function __construct(
        private AsyncProjectionService $projections,
        private WorkflowAsyncExecutionService $workflows,
    ) {}

    public function dispatchAfterTransition(Mission $mission, User $actor, string $action): void
    {
        $this->projections->dispatchMissionProjection($mission);
        $this->workflows->dispatchStageSideEffects($mission, $actor, $action);
    }
}
