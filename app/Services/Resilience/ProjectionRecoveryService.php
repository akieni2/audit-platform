<?php

namespace App\Services\Resilience;

use App\Models\Mission;
use App\Services\Runtime\AsyncProjectionService;

class ProjectionRecoveryService
{
    public function __construct(private AsyncProjectionService $async) {}

    public function refresh(Mission $mission): void
    {
        $this->async->dispatchMissionProjection($mission);
    }

    public function detectOrphans(Mission $mission): array
    {
        $orphans = [];

        if ($mission->workflow_instance_id === null) {
            $orphans[] = 'missing_workflow_instance';
        }

        if ($mission->riskProjection === null) {
            $orphans[] = 'missing_risk_projection';
        }

        return $orphans;
    }
}
