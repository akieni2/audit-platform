<?php

namespace App\Listeners;

use App\Domain\Questionnaires\Events\EntretienResponsesRecorded;
use App\Domain\Risk\Events\RiskPromoted;
use App\Jobs\RefreshMissionRiskProjectionJob;
use App\Services\Risk\MissionRiskProjectionService;

class RefreshMissionRiskProjection
{
    public function __construct(
        private MissionRiskProjectionService $projections,
    ) {}

    public function handle(object $event): void
    {
        $missionId = null;
        $correlationId = null;

        if ($event instanceof EntretienResponsesRecorded) {
            $missionId = (int) $event->entretien->mission_id;
            $correlationId = $event->correlationId;
        }

        if ($event instanceof RiskPromoted) {
            $missionId = (int) $event->identifiedRisk->mission_id;
            $correlationId = $event->correlationId;
        }

        if ($missionId === null || $missionId <= 0) {
            return;
        }

        if ((bool) config('core_runtime.async_projection_refresh', true)) {
            RefreshMissionRiskProjectionJob::dispatch($missionId, $correlationId);

            return;
        }

        $this->projections->refreshForMissionId($missionId, false, $correlationId);
    }
}
