<?php

namespace App\Services\Risk;

use App\Domain\Risk\Enums\CriticalityLevel;
use App\Models\IdentifiedRisk;
use App\Models\Risque;

final class EnterpriseHeatmapService
{
    public function __construct(
        private RiskRegistryQueryService $registry,
        private RiskScoringEngine $scoring,
        private CriticalityEvaluationService $criticality,
    ) {}

    /**
     * @return array{
     *   combined: array{counts: array<string, int>, matrix: array<int, array<int, array<string, mixed>>>},
     *   registry: array{counts: array<string, int>, matrix: array<int, array<int, array<string, mixed>>>},
     *   residual: array{counts: array<string, int>, matrix: array<int, array<int, array<string, mixed>>>},
     *   buckets: array<string, int>
     * }
     */
    public function mission(int $missionId): array
    {
        return $this->snapshot(['mission_id' => $missionId]);
    }

    /**
     * @return array{
     *   combined: array{counts: array<string, int>, matrix: array<int, array<int, array<string, mixed>>>},
     *   registry: array{counts: array<string, int>, matrix: array<int, array<int, array<string, mixed>>>},
     *   residual: array{counts: array<string, int>, matrix: array<int, array<int, array<string, mixed>>>},
     *   buckets: array<string, int>
     * }
     */
    public function department(int $departmentId): array
    {
        return $this->snapshot(['department_id' => $departmentId]);
    }

    /**
     * @return array{
     *   combined: array{counts: array<string, int>, matrix: array<int, array<int, array<string, mixed>>>},
     *   registry: array{counts: array<string, int>, matrix: array<int, array<int, array<string, mixed>>>},
     *   residual: array{counts: array<string, int>, matrix: array<int, array<int, array<string, mixed>>>},
     *   buckets: array<string, int>
     * }
     */
    public function national(): array
    {
        return $this->snapshot();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *   combined: array{counts: array<string, int>, matrix: array<int, array<int, array<string, mixed>>>},
     *   registry: array{counts: array<string, int>, matrix: array<int, array<int, array<string, mixed>>>},
     *   residual: array{counts: array<string, int>, matrix: array<int, array<int, array<string, mixed>>>},
     *   buckets: array<string, int>
     * }
     */
    public function snapshot(array $filters = []): array
    {
        $official = $this->registry->registry($filters);
        $intake = $this->registry->intake($filters);

        $registryCounts = $this->countsForRegistry($official);
        $combinedCounts = $registryCounts;
        foreach ($this->countsForIntake($intake) as $key => $count) {
            $combinedCounts[$key] = ($combinedCounts[$key] ?? 0) + $count;
        }

        $residualCounts = $this->countsForResidual($official);

        return [
            'combined' => [
                'counts' => $combinedCounts,
                'matrix' => $this->matrixFromCounts($combinedCounts),
            ],
            'registry' => [
                'counts' => $registryCounts,
                'matrix' => $this->matrixFromCounts($registryCounts),
            ],
            'residual' => [
                'counts' => $residualCounts,
                'matrix' => $this->matrixFromCounts($residualCounts),
            ],
            'buckets' => $this->bucketize($combinedCounts),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Risque>  $official
     * @return array<string, int>
     */
    private function countsForRegistry($official): array
    {
        $counts = [];

        foreach ($official as $risk) {
            $x = (int) ($risk->heatmap_x ?: $risk->impact_inherent);
            $y = (int) ($risk->heatmap_y ?: $risk->probabilite_inherent);
            $key = $x.'-'.$y;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, IdentifiedRisk>  $intake
     * @return array<string, int>
     */
    private function countsForIntake($intake): array
    {
        $counts = [];

        foreach ($intake as $risk) {
            $package = $this->scoring->inherent($risk->probability, $risk->impact, $risk->criticality);
            $key = $package['heatmap_x'].'-'.$package['heatmap_y'];
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Risque>  $official
     * @return array<string, int>
     */
    private function countsForResidual($official): array
    {
        $counts = [];

        foreach ($official as $risk) {
            if ($risk->impact_residuel === null || $risk->probabilite_residuel === null) {
                continue;
            }

            [$x, $y] = $this->scoring->heatmapPosition(
                (int) $risk->impact_residuel,
                (int) $risk->probabilite_residuel,
            );
            $key = $x.'-'.$y;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function matrixFromCounts(array $counts): array
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

    /**
     * @param  array<string, int>  $counts
     * @return array<string, int>
     */
    private function bucketize(array $counts): array
    {
        $buckets = [
            CriticalityLevel::Low->value => 0,
            CriticalityLevel::Medium->value => 0,
            CriticalityLevel::High->value => 0,
            CriticalityLevel::Critical->value => 0,
        ];

        foreach ($counts as $key => $count) {
            [$impact, $probability] = array_map('intval', explode('-', $key));
            $level = $this->criticality->levelFromScore($impact * $probability);
            $buckets[$level->value] = ($buckets[$level->value] ?? 0) + $count;
        }

        return $buckets;
    }
}
