<?php

namespace App\Observers;

use App\Models\Risque;
use App\Services\Governance\CrossDepartmentRiskRoutingService;

class RisqueObserver
{
    public function created(Risque $risque): void
    {
        app(CrossDepartmentRiskRoutingService::class)->analyzeAndRoute($risque->fresh());
    }

    public function updated(Risque $risque): void
    {
        if ($risque->wasChanged('description')) {
            app(CrossDepartmentRiskRoutingService::class)->analyzeAndRoute($risque->fresh());
        }
    }
}
