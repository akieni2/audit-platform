<?php

namespace App\Observers;

use App\Models\Risque;
use App\Services\Governance\CrossDepartmentRiskRoutingService;
use App\Services\Governance\ExecutiveDashboardService;
use App\Services\Risk\MissionRiskProjectionService;

class RisqueObserver
{
    public function created(Risque $risque): void
    {
        app(CrossDepartmentRiskRoutingService::class)->analyzeAndRoute($risque->fresh());
        ExecutiveDashboardService::flushNationalKpisCache();
        $this->refreshMissionProjection($risque);
    }

    public function updated(Risque $risque): void
    {
        if ($risque->wasChanged('description')) {
            app(CrossDepartmentRiskRoutingService::class)->analyzeAndRoute($risque->fresh());
        }
        ExecutiveDashboardService::flushNationalKpisCache();
        $this->refreshMissionProjection($risque);
    }

    private function refreshMissionProjection(Risque $risque): void
    {
        $risque->loadMissing('actif.processus.mission');
        $missionId = $risque->actif?->processus?->mission_id;
        if ($missionId !== null) {
            app(MissionRiskProjectionService::class)->refreshForMissionId((int) $missionId);
        }
    }
}
