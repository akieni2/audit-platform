<?php

namespace App\Services\Risk;

use App\Repositories\Contracts\RiskRepositoryInterface;
use Illuminate\Support\Collection;

final class RiskDashboardService
{
    public function __construct(
        private RiskRepositoryInterface $risks,
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
        return [
            'critical_count' => $this->risks->countCriticalForMission($missionId),
            'top_risks' => $this->risks->topByInherentScore($missionId, 10),
            'monthly' => $this->risks->monthlyCreationCounts($missionId),
            'by_department' => $this->risks->countsByDepartment($missionId),
        ];
    }
}
