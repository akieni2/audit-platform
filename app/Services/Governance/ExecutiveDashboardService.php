<?php

namespace App\Services\Governance;

use App\Domain\Risk\Enums\CriticalityLevel;
use App\Models\Mission;
use App\Models\Risque;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class ExecutiveDashboardService
{
    public const NATIONAL_KPIS_CACHE_KEY = 'audit:governance:national_kpis:v1';

    /**
     * @return array<string, int|float>
     */
    public function nationalKpis(): array
    {
        $ttl = max(30, (int) config('audit.kpi_cache_ttl', 120));

        return Cache::remember(self::NATIONAL_KPIS_CACHE_KEY, $ttl, fn (): array => $this->computeNationalKpis());
    }

    /**
     * Invalidation explicite (workflow mission, risques, missions).
     */
    public static function flushNationalKpisCache(): void
    {
        Cache::forget(self::NATIONAL_KPIS_CACHE_KEY);
    }

    /**
     * @return array<string, int|float>
     */
    private function computeNationalKpis(): array
    {
        $missionsOuvertes = Mission::query()
            ->whereIn('mission_status', [
                Mission::STATUS_BROUILLON,
                Mission::STATUS_EN_COURS,
            ])
            ->count();

        $missionsCloturees = Mission::query()
            ->whereIn('mission_status', [
                Mission::STATUS_CLOTUREE,
                Mission::STATUS_VALIDEE_IS,
                Mission::STATUS_VALIDEE_COPRI,
            ])
            ->count();

        $risquesCritiques = Risque::query()
            ->where(function ($q) {
                $q->where('criticite_inherent', CriticalityLevel::Critique->value)
                    ->orWhere('criticite_residuel', CriticalityLevel::Critique->value);
            })
            ->count();

        $risquesTransversaux = Risque::query()->where('cross_department', true)->count();

        return [
            'missions_ouvertes' => $missionsOuvertes,
            'missions_cloturees' => $missionsCloturees,
            'risques_critiques' => $risquesCritiques,
            'risques_transversaux' => $risquesTransversaux,
        ];
    }

    /**
     * Missions clôturées en attente de validation Inspection des Services.
     *
     * @return Collection<int, Mission>
     */
    public function missionsAwaitingInspection(): Collection
    {
        return Mission::query()
            ->with(['department:id,code,name'])
            ->where('mission_status', Mission::STATUS_CLOTUREE)
            ->orderByDesc('updated_at')
            ->limit(60)
            ->get();
    }

    /**
     * Missions validées IS en attente de validation COPRI.
     *
     * @return Collection<int, Mission>
     */
    public function missionsAwaitingCopri(): Collection
    {
        return Mission::query()
            ->with(['department:id,code,name'])
            ->where('mission_status', Mission::STATUS_VALIDEE_IS)
            ->orderByDesc('updated_at')
            ->limit(60)
            ->get();
    }
}
