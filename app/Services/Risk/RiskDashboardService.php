<?php

namespace App\Services\Risk;

use Illuminate\Support\Collection;

final class RiskDashboardService
{
    public function __construct(
        private MissionRiskDashboardService $missions,
        private RiskRegistryQueryService $registry,
    ) {}

    /**
     * @return array{
     *   critical_count: int,
     *   top_risks: Collection,
     *   monthly: array<string, int>,
     *   by_department: array<string, int>
     * }
     */
    public function snapshot(int $missionId): array
    {
        $snapshot = $this->missions->snapshot($missionId);

        return [
            'critical_count' => $snapshot['critical_open'],
            'top_risks' => $this->registry->registry(['mission_id' => $missionId])->take(10),
            'monthly' => $snapshot['monthly'],
            'by_department' => $snapshot['by_department'],
        ];
    }
}
