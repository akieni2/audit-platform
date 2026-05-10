<?php

namespace App\Observers;

use App\Models\Risque;
use App\Services\Governance\CrossDepartmentRiskRoutingService;
use App\Services\Governance\ExecutiveDashboardService;

class RisqueObserver
{
    public function created(Risque $risque): void
    {
        app(CrossDepartmentRiskRoutingService::class)->analyzeAndRoute($risque->fresh());
        ExecutiveDashboardService::flushNationalKpisCache();
    }

    public function updated(Risque $risque): void
    {
        if ($risque->wasChanged('description')) {
            app(CrossDepartmentRiskRoutingService::class)->analyzeAndRoute($risque->fresh());
        }
        ExecutiveDashboardService::flushNationalKpisCache();
    }
}
