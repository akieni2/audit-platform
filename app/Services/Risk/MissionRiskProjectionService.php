<?php

namespace App\Services\Risk;

use App\Domain\Risk\Enums\CriticalityLevel;
use App\Domain\Risk\Enums\RiskLifecycleStatus;
use App\Models\IdentifiedRisk;
use App\Models\Mission;
use App\Models\MissionRiskProjection;
use App\Models\Risque;

final class MissionRiskProjectionService
{
    public function __construct(
        private HeatmapProjectionService $heatmaps,
    ) {}

    public function refreshForMission(Mission $mission): MissionRiskProjection
    {
        return $this->refreshForMissionId((int) $mission->id);
    }

    public function refreshForMissionId(int $missionId): MissionRiskProjection
    {
        $intake = IdentifiedRisk::query()
            ->where('mission_id', $missionId)
            ->get(['lifecycle_status']);

        $official = Risque::query()
            ->whereHas('actif.processus', fn ($q) => $q->where('mission_id', $missionId));

        $inherentHeatmap = $this->heatmaps->inherentForMission($missionId);
        $residualHeatmap = $this->heatmaps->residualForMission($missionId);

        return MissionRiskProjection::query()->updateOrCreate(
            ['mission_id' => $missionId],
            [
                'intake_detected_count' => $intake->where('lifecycle_status', RiskLifecycleStatus::Detected->value)->count(),
                'intake_reviewed_count' => $intake->where('lifecycle_status', RiskLifecycleStatus::Reviewed->value)->count(),
                'intake_qualified_count' => $intake->where('lifecycle_status', RiskLifecycleStatus::Qualified->value)->count(),
                'intake_approved_count' => $intake->where('lifecycle_status', RiskLifecycleStatus::Approved->value)->count(),
                'intake_promoted_count' => $intake->where('lifecycle_status', RiskLifecycleStatus::Promoted->value)->count(),
                'official_count' => (clone $official)->count(),
                'official_critical_count' => (clone $official)
                    ->where('criticite_inherent', CriticalityLevel::Critique->value)
                    ->count(),
                'official_residual_critical_count' => (clone $official)
                    ->where('criticite_residuel', CriticalityLevel::Critique->value)
                    ->count(),
                'inherent_heatmap' => $inherentHeatmap['counts'],
                'residual_heatmap' => $residualHeatmap['counts'],
                'refreshed_at' => now(),
            ]
        );
    }
}
