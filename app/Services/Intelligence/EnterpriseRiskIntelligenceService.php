<?php

namespace App\Services\Intelligence;

use App\Services\Risk\RiskRegistryQueryService;

class EnterpriseRiskIntelligenceService
{
    public function __construct(
        private RiskRegistryQueryService $registry,
        private RiskCorrelationService $correlations,
        private RiskTrendAnalysisService $trends,
        private CrossDepartmentRiskAggregator $aggregator,
        private NationalHeatmapService $heatmaps,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function snapshot(array $filters = []): array
    {
        $kpis = $this->registry->kpis($filters);
        $lifecycle = $this->registry->lifecycleBreakdown($filters);
        $criticality = $this->registry->criticalityBreakdown($filters);
        $maturity = $this->maturityIndex($kpis, $lifecycle);

        return [
            'kpis' => $kpis,
            'lifecycle' => $lifecycle,
            'criticality' => $criticality,
            'trends' => $this->trends->monthly($filters),
            'recurring' => $this->trends->recurring($filters),
            'correlations' => $this->correlations->correlate($filters),
            'departments' => $this->aggregator->aggregate($filters),
            'national_heatmap' => $this->heatmaps->snapshot($filters),
            'maturity' => $maturity,
        ];
    }

    /**
     * @param  array{critical_open:int, in_review:int, promoted:int, mitigated:int, residual_exposure:int, total_registry:int, total_intake:int}  $kpis
     * @param  array<string, int>  $lifecycle
     * @return array<string, int|float>
     */
    public function maturityIndex(array $kpis, array $lifecycle): array
    {
        $registry = max(1, (int) ($kpis['total_registry'] ?? 0));
        $controlled = (int) ($kpis['mitigated'] ?? 0) + (int) ($lifecycle['closed'] ?? 0);
        $inReview = (int) ($kpis['in_review'] ?? 0);
        $criticalOpen = (int) ($kpis['critical_open'] ?? 0);

        $score = max(0, min(100, (int) round((($controlled * 100) / $registry) - ($criticalOpen * 2) - $inReview)));

        return [
            'score' => $score,
            'compliance_rate' => max(0, min(100, (int) round((($controlled + (int) ($kpis['promoted'] ?? 0)) * 100) / $registry))),
            'risk_maturity' => $score >= 75 ? 'advanced' : ($score >= 50 ? 'managed' : 'emerging'),
        ];
    }
}
