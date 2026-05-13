<?php

namespace App\Services\Risk;

final class DepartmentRiskDashboardService
{
    public function __construct(
        private RiskRegistryQueryService $registry,
        private EnterpriseHeatmapService $heatmaps,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function snapshot(int $departmentId): array
    {
        $filters = ['department_id' => $departmentId];

        return [
            ...$this->registry->kpis($filters),
            'by_department' => $this->registry->risksByDepartment($filters),
            'monthly' => $this->registry->trends($filters),
            'top_services' => $this->registry->topServicesExposed($filters),
            'lifecycle' => $this->registry->lifecycleBreakdown($filters),
            'criticality' => $this->registry->criticalityBreakdown($filters),
            'heatmap' => $this->heatmaps->department($departmentId),
        ];
    }
}
