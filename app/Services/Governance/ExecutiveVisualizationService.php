<?php

namespace App\Services\Governance;

class ExecutiveVisualizationService
{
    public function __construct(
        private EnterpriseKpiRenderer $kpis,
        private DashboardWidgetService $widgets,
        private AlertVisualizationService $alerts,
    ) {}

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    public function nationalDashboard(array $snapshot): array
    {
        return [
            'kpis' => $this->kpis->render($snapshot),
            'widgets' => $this->widgets->build($snapshot),
            'alerts' => $this->alerts->build($snapshot),
            'live_feed' => collect(data_get($snapshot, 'intelligence.recurring', []))
                ->take(5)
                ->map(fn ($risk) => [
                    'title' => data_get($risk, 'label', 'Signal intelligence'),
                    'message' => 'Départements: '.implode(', ', data_get($risk, 'departments', [])),
                ])
                ->values()
                ->all(),
            'charts' => [
                'maturity_score' => data_get($snapshot, 'intelligence.maturity.score', 0),
                'compliance_rate' => data_get($snapshot, 'intelligence.maturity.compliance_rate', 0),
                'risk_maturity' => data_get($snapshot, 'intelligence.maturity.risk_maturity', 'emerging'),
            ],
        ];
    }
}
