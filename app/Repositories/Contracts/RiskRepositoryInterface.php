<?php

namespace App\Repositories\Contracts;

use App\Models\Risque;
use Illuminate\Support\Collection;

interface RiskRepositoryInterface
{
    /** @return Collection<int, Risque> */
    public function forMission(int $missionId): Collection;

    public function countCriticalForMission(int $missionId): int;

    /** @return Collection<int, Risque> */
    public function topByInherentScore(int $missionId, int $limit = 10): Collection;

    /**
     * Évolution mensuelle : nombre de risques créés par mois (clé YYYY-MM).
     *
     * @return array<string, int>
     */
    public function monthlyCreationCounts(int $missionId, int $monthsBack = 12): array;

    /**
     * Répartition par département.
     *
     * @return array<string, int>
     */
    public function countsByDepartment(int $missionId): array;

    /**
     * Grille heatmap 5×5 : clé "impact-prob" => nombre de risques (inherent).
     *
     * @return array<string, int>
     */
    public function inherentHeatmapCounts(int $missionId): array;
}
