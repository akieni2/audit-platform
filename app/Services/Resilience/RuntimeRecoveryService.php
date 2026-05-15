<?php

namespace App\Services\Resilience;

use App\Models\Mission;
use App\Models\User;
use App\Services\Workflow\WorkflowExecutionService;

class RuntimeRecoveryService
{
    public function __construct(
        private WorkflowExecutionService $execution,
        private IntegrityRepairService $repair,
    ) {}

    public function retryCurrentStage(Mission $mission, User $actor, ?string $comment = null): bool
    {
        $instance = $mission->workflowInstance;

        if ($instance === null || $instance->currentStage === null) {
            return false;
        }

        return $this->execution->retryStage($instance, $instance->currentStage, $actor, $comment);
    }

    public function repairAndRetry(Mission $mission, User $actor): bool
    {
        $this->repair->repairMissionProjections($mission);

        return $this->retryCurrentStage($mission, $actor, 'Recovery orchestration');
    }
}
