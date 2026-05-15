<?php

namespace App\Services\Resilience;

use App\Models\Mission;
use App\Models\ProjectionIntegrityCheck;
use Illuminate\Support\Facades\Schema;

class IntegrityRepairService
{
    public function __construct(private ProjectionRecoveryService $projections) {}

    public function repairMissionProjections(Mission $mission): void
    {
        $this->projections->refresh($mission);

        if (Schema::hasTable('projection_integrity_checks')) {
            ProjectionIntegrityCheck::query()->create([
                'scope_type' => 'mission',
                'scope_id' => $mission->id,
                'status' => 'repaired',
                'details' => ['source' => 'integrity_repair'],
                'checked_at' => now(),
            ]);
        }
    }
}
