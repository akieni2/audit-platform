<?php

namespace App\Services\Risk;

use App\Repositories\Contracts\RiskRepositoryInterface;

final class HeatmapProjectionService
{
    public function __construct(
        private RiskRepositoryInterface $risks,
        private CriticalityEvaluationService $criticality,
    ) {}

    /**
     * @return array{
     *   counts: array<string, int>,
     *   matrix: array<int, array<int, array{
     *      impact: int,
     *      probabilite: int,
     *      score: int,
     *      level: \App\Domain\Risk\Enums\CriticalityLevel,
     *      criticite: string,
     *      cell_classes: string,
     *      heatmap_color: string,
     *      count: int
     *   }>>
     * }
     */
    public function inherentForMission(int $missionId): array
    {
        $counts = $this->risks->inherentHeatmapCounts($missionId);

        return [
            'counts' => $counts,
            'matrix' => $this->matrixFromCounts($counts),
        ];
    }

    /**
     * @return array{
     *   counts: array<string, int>,
     *   matrix: array<int, array<int, array{
     *      impact: int,
     *      probabilite: int,
     *      score: int,
     *      level: \App\Domain\Risk\Enums\CriticalityLevel,
     *      criticite: string,
     *      cell_classes: string,
     *      heatmap_color: string,
     *      count: int
     *   }>>
     * }
     */
    public function residualForMission(int $missionId): array
    {
        $counts = $this->risks->residualHeatmapCounts($missionId);

        return [
            'counts' => $counts,
            'matrix' => $this->matrixFromCounts($counts),
        ];
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<int, array<int, array{
     *      impact: int,
     *      probabilite: int,
     *      score: int,
     *      level: \App\Domain\Risk\Enums\CriticalityLevel,
     *      criticite: string,
     *      cell_classes: string,
     *      heatmap_color: string,
     *      count: int
     * }>>
     */
    public function matrixFromCounts(array $counts): array
    {
        $rows = [];
        for ($probability = 5; $probability >= 1; $probability--) {
            $row = [];
            for ($impact = 1; $impact <= 5; $impact++) {
                $score = $impact * $probability;
                $level = $this->criticality->levelFromScore($score);
                $key = $impact.'-'.$probability;
                $row[] = [
                    'impact' => $impact,
                    'probabilite' => $probability,
                    'score' => $score,
                    'level' => $level,
                    'criticite' => $level->value,
                    'cell_classes' => $this->criticality->heatmapCellClasses($level),
                    'heatmap_color' => $this->criticality->heatmapTintForCoordinates($impact, $probability),
                    'count' => (int) ($counts[$key] ?? 0),
                ];
            }
            $rows[] = $row;
        }

        return $rows;
    }
}
