<?php

namespace App\Services\Observability;

use App\Models\Mission;
use App\Models\User;
use App\Services\Resilience\ProjectionRecoveryService;

class RuntimeDiagnosticsService
{
    public function __construct(private ProjectionRecoveryService $recovery) {}

    /**
     * @return array<string, mixed>
     */
    public function forMission(Mission $mission, User $actor): array
    {
        return [
            'mission_id' => $mission->id,
            'workflow_instance_id' => $mission->workflow_instance_id,
            'orphans' => $this->recovery->detectOrphans($mission),
            'actor_can_view' => $actor->can('view', $mission),
        ];
    }
}
