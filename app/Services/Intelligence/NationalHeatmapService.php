<?php

namespace App\Services\Intelligence;

use App\Services\Risk\EnterpriseHeatmapService;

class NationalHeatmapService
{
    public function __construct(
        private EnterpriseHeatmapService $heatmap,
        private CrossDepartmentRiskAggregator $aggregator,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function snapshot(array $filters = []): array
    {
        return [
            'heatmap' => $this->heatmap->snapshot($filters),
            'departments' => $this->aggregator->aggregate($filters),
        ];
    }
}
