<?php

namespace App\Services\Risk;

final class MissionRiskDashboardService
{
    public function __construct(
        private RiskRegistryQueryService $registry,
        private EnterpriseHeatmapService $heatmaps,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function snapshot(int $missionId): array
    {
        $filters = ['mission_id' => $missionId];

        return [
            ...$this->registry->kpis($filters),
            'by_department' => $this->registry->risksByDepartment($filters),
            'monthly' => $this->registry->trends($filters),
            'top_services' => $this->registry->topServicesExposed($filters),
            'lifecycle' => $this->registry->lifecycleBreakdown($filters),
            'criticality' => $this->registry->criticalityBreakdown($filters),
            'heatmap' => $this->heatmaps->mission($missionId),
        ];
    }
}
