<?php

namespace App\Services\Governance;

use App\Domain\Risk\Enums\CriticalityLevel;
use App\Models\Mission;
use App\Models\Risque;

final class ExecutiveDashboardService
{
    /**
     * @return array<string, int|float>
     */
    public function nationalKpis(): array
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
}
