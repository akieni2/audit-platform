<?php

namespace App\Services\Runtime;

use App\Jobs\RefreshMissionRiskProjectionJob;
use App\Models\Mission;

class AsyncProjectionService
{
    public function dispatchMissionProjection(Mission $mission): void
    {
        if (! config('core_runtime.async_projection_refresh', true)) {
            return;
        }

        RefreshMissionRiskProjectionJob::dispatch($mission->id)
            ->onQueue((string) config('enterprise_hardening.projection_queue', 'projections'));
    }
}
