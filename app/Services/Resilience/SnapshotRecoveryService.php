<?php

namespace App\Services\Resilience;

use App\Models\Mission;
use App\Models\SwotSnapshot;
use Illuminate\Support\Facades\Schema;

class SnapshotRecoveryService
{
    public function latestSwotSnapshot(Mission $mission): ?SwotSnapshot
    {
        if (! Schema::hasTable('swot_snapshots')) {
            return null;
        }

        return $mission->swotSnapshots()->latest('captured_at')->first();
    }
}
