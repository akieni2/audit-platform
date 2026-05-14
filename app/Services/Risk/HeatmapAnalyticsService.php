<?php

namespace App\Services\Risk;

use Illuminate\Support\Collection;

class HeatmapAnalyticsService
{
    /**
     * @param  array<string, mixed>  $dashboard
     * @param  Collection<int, mixed>  $risks
     * @return array<string, mixed>
     */
    public function build(array $dashboard, Collection $risks): array
    {
        return [
            'critical_count' => (int) data_get($dashboard, 'critical_count', data_get($dashboard, 'critical_open', 0)),
            'monthly' => collect(data_get($dashboard, 'monthly', []))->sortKeys()->all(),
            'by_department' => data_get($dashboard, 'by_department', []),
            'top_risks' => collect(data_get($dashboard, 'top_risks', []))
                ->take(5)
                ->map(fn ($risk) => [
                    'description' => (string) data_get($risk, 'description', 'Risque'),
                    'score' => data_get($risk, 'score_inherent'),
                    'department' => data_get($risk, 'departement'),
                ])
                ->values()
                ->all(),
            'density' => [
                'total_risks' => $risks->count(),
                'high_density_departments' => $risks->groupBy(fn ($risk) => (string) data_get($risk, 'departement', 'Non affecté'))
                    ->map->count()
                    ->sortDesc()
                    ->take(3)
                    ->all(),
            ],
        ];
    }
}
