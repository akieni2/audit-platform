<?php

namespace App\Services\Runtime;

use App\Models\Mission;
use App\Models\User;
use App\Services\Audit\ImmutableAuditTrailService;

class WorkflowAsyncExecutionService
{
    public function __construct(private ImmutableAuditTrailService $audit) {}

    public function dispatchStageSideEffects(Mission $mission, User $actor, string $action): void
    {
        $this->audit->record(
            eventType: 'workflow_async_dispatch',
            module: 'workflows',
            description: 'Dispatch async runtime — mission #'.$mission->id,
            user: $actor,
            payload: ['action' => $action, 'mission_id' => $mission->id],
            resourceType: Mission::class,
            resourceId: $mission->id,
        );
    }
}
