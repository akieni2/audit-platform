<?php

namespace App\Services\Risk;

use App\Models\Mission;
use Illuminate\Support\Collection;

class HeatmapVisualizationService
{
    public function __construct(
        private RiskMatrixRenderer $renderer,
        private HeatmapAnalyticsService $analytics,
        private RiskClusteringService $clustering,
    ) {}

    /**
     * @param  array<int, array<int, array<string, mixed>>>  $heatmapRows
     * @param  array<int, array<int, array<string, mixed>>>  $residualHeatmapRows
     * @param  array<string, mixed>  $dashboard
     * @param  Collection<int, mixed>  $risks
     * @return array<string, mixed>
     */
    public function build(
        Mission $mission,
        array $heatmapRows,
        array $residualHeatmapRows,
        array $dashboard,
        Collection $risks,
    ): array {
        return [
            'mission' => [
                'id' => $mission->id,
                'label' => $mission->organisation,
            ],
            'modes' => [
                'inherent' => $this->renderer->render($heatmapRows, 'inherent'),
                'residual' => $this->renderer->render($residualHeatmapRows, 'residual'),
            ],
            'filters' => [
                'scopes' => ['Mission', 'Département', 'National'],
                'active_scope' => 'Mission',
                'departments' => array_keys((array) data_get($dashboard, 'by_department', [])),
            ],
            'analytics' => $this->analytics->build($dashboard, $risks),
            'clusters' => $this->clustering->cluster($risks),
        ];
    }
}
