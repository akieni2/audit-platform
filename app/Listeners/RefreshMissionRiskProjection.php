<?php

namespace App\Listeners;

use App\Domain\Questionnaires\Events\EntretienResponsesRecorded;
use App\Domain\Risk\Events\RiskPromoted;
use App\Services\Risk\MissionRiskProjectionService;

class RefreshMissionRiskProjection
{
    public function __construct(
        private MissionRiskProjectionService $projections,
    ) {}

    public function handle(object $event): void
    {
        if ($event instanceof EntretienResponsesRecorded) {
            $this->projections->refreshForMissionId((int) $event->entretien->mission_id);

            return;
        }

        if ($event instanceof RiskPromoted) {
            $this->projections->refreshForMissionId((int) $event->identifiedRisk->mission_id);
        }
    }
}
