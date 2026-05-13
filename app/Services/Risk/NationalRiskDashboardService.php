<?php

namespace App\Services\Risk;

final class NationalRiskDashboardService
{
    public function __construct(
        private RiskRegistryQueryService $registry,
        private EnterpriseHeatmapService $heatmaps,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return [
            ...$this->registry->kpis(),
            'by_department' => $this->registry->risksByDepartment(),
            'monthly' => $this->registry->trends(),
            'top_services' => $this->registry->topServicesExposed(),
            'lifecycle' => $this->registry->lifecycleBreakdown(),
            'criticality' => $this->registry->criticalityBreakdown(),
            'heatmap' => $this->heatmaps->national(),
        ];
    }
}
