<?php

namespace App\Services\Risk;

use Illuminate\Support\Collection;

class RiskClusteringService
{
    /**
     * @param  Collection<int, mixed>  $risks
     * @return array<int, array<string, mixed>>
     */
    public function cluster(Collection $risks): array
    {
        return $risks
            ->groupBy(fn ($risk) => (string) data_get($risk, 'departement', 'Non affecté'))
            ->map(function (Collection $group, string $department) {
                return [
                    'label' => $department,
                    'count' => $group->count(),
                    'critical' => $group->filter(fn ($risk) => str_contains(strtolower((string) data_get($risk, 'criticite_inherent', '')), 'crit'))->count(),
                    'average_score' => round($group->avg(fn ($risk) => (float) data_get($risk, 'score_inherent', 0)), 1),
                    'items' => $group->take(5)->map(fn ($risk) => [
                        'id' => data_get($risk, 'id'),
                        'description' => (string) data_get($risk, 'description', 'Risque'),
                        'score' => data_get($risk, 'score_inherent'),
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}
