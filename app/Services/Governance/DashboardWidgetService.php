<?php

namespace App\Services\Governance;

class DashboardWidgetService
{
    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<int, array<string, mixed>>
     */
    public function build(array $snapshot): array
    {
        return [
            [
                'title' => 'Maturité consolidée',
                'value' => data_get($snapshot, 'intelligence.maturity.score', 0),
                'caption' => 'Score national',
            ],
            [
                'title' => 'Compliance rate',
                'value' => data_get($snapshot, 'intelligence.maturity.compliance_rate', 0).'%',
                'caption' => 'Taux consolidé',
            ],
            [
                'title' => 'Risk maturity',
                'value' => data_get($snapshot, 'intelligence.maturity.risk_maturity', 'emerging'),
                'caption' => 'Niveau national',
            ],
        ];
    }
}
