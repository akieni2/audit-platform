<?php

namespace App\Repositories;

use App\Domain\Risk\Enums\CriticalityLevel;
use App\Models\Risque;
use App\Repositories\Contracts\RiskRepositoryInterface;
use Illuminate\Support\Collection;

final class EloquentRiskRepository implements RiskRepositoryInterface
{
    public function forMission(int $missionId): Collection
    {
        return Risque::query()
            ->whereHas('actif.processus.mission', fn ($q) => $q->where('missions.id', $missionId))
            ->with(['actif.processus.mission', 'controles'])
            ->orderByDesc('score_inherent')
            ->get();
    }

    public function countCriticalForMission(int $missionId): int
    {
        return Risque::query()
            ->whereHas('actif.processus.mission', fn ($q) => $q->where('missions.id', $missionId))
            ->where(function ($q) {
                $q->where('criticite_inherent', CriticalityLevel::Critical->value)
                    ->orWhere('criticite_residuel', CriticalityLevel::Critical->value);
            })
            ->count();
    }

    public function topByInherentScore(int $missionId, int $limit = 10): Collection
    {
        return $this->forMission($missionId)->take($limit);
    }

    public function monthlyCreationCounts(int $missionId, int $monthsBack = 12): array
    {
        $risques = Risque::query()
            ->whereHas('actif.processus.mission', fn ($q) => $q->where('missions.id', $missionId))
            ->where('created_at', '>=', now()->subMonths($monthsBack)->startOfMonth())
            ->get(['created_at']);

        $out = [];
        foreach ($risques as $risque) {
            $ym = $risque->created_at->format('Y-m');
            $out[$ym] = ($out[$ym] ?? 0) + 1;
        }
        ksort($out);

        return $out;
    }

    public function countsByDepartment(int $missionId): array
    {
        $departements = Risque::query()
            ->whereHas('actif.processus.mission', fn ($q) => $q->where('missions.id', $missionId))
            ->pluck('departement');

        $out = [];
        foreach ($departements as $dept) {
            $key = $dept !== null && trim((string) $dept) !== ''
                ? trim((string) $dept)
                : 'Non renseigné';
            $out[$key] = ($out[$key] ?? 0) + 1;
        }
        arsort($out);

        return $out;
    }

    public function inherentHeatmapCounts(int $missionId): array
    {
        $risques = Risque::query()
            ->whereHas('actif.processus.mission', fn ($q) => $q->where('missions.id', $missionId))
            ->get(['impact_inherent', 'probabilite_inherent']);

        $counts = [];
        foreach ($risques as $risque) {
            $key = $risque->impact_inherent.'-'.$risque->probabilite_inherent;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        return $counts;
    }

    public function residualHeatmapCounts(int $missionId): array
    {
        $risques = Risque::query()
            ->whereHas('actif.processus.mission', fn ($q) => $q->where('missions.id', $missionId))
            ->whereNotNull('impact_residuel')
            ->whereNotNull('probabilite_residuel')
            ->get(['impact_residuel', 'probabilite_residuel']);

        $counts = [];
        foreach ($risques as $risque) {
            $key = $risque->impact_residuel.'-'.$risque->probabilite_residuel;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        return $counts;
    }
}
