<?php

namespace App\Services\Dgcpt;

use App\Domain\Dgcpt\Enums\TreasuryEntityType;
use App\Models\Dgcpt\TreasuryEntity;
use App\Models\Mission;
use App\Models\Risque;
use App\Services\Risk\EnterpriseHeatmapService;
/**
 * Consolidation nationale / provinciale — s'appuie sur le moteur heatmap existant.
 */
final class DgcptNationalConsolidationService
{
    public function __construct(
        private EnterpriseHeatmapService $heatmaps,
        private DgcptHierarchyService $hierarchy,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function nationalSnapshot(): array
    {
        $provinces = TreasuryEntity::query()
            ->where('entity_type', TreasuryEntityType::Provincial->value)
            ->active()
            ->orderBy('province')
            ->get();

        $missions = Mission::query()->count();
        $missionsWithContext = Mission::query()
            ->whereNotNull('treasury_entity_id')
            ->count();

        return [
            'provinces' => $provinces->map(fn (TreasuryEntity $e) => $this->provinceRow($e))->values()->all(),
            'totals' => [
                'missions' => $missions,
                'missions_contextualized' => $missionsWithContext,
                'entities' => TreasuryEntity::query()->active()->count(),
            ],
            'heatmap' => $this->heatmaps->national(),
            'hierarchy' => $this->hierarchy->tree(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function provinceSnapshot(TreasuryEntity $entity): array
    {
        $missionIds = Mission::query()
            ->where('treasury_entity_id', $entity->id)
            ->pluck('id');

        $riskQuery = Risque::query()
            ->whereHas('actif.processus.mission', fn ($q) => $q->where('treasury_entity_id', $entity->id));

        return [
            'entity' => [
                'id' => $entity->id,
                'code' => $entity->code,
                'name' => $entity->name,
                'province' => $entity->province,
            ],
            'missions_count' => $missionIds->count(),
            'risks_count' => (clone $riskQuery)->count(),
            'critical_risks' => (clone $riskQuery)->where('criticite_residuel', 'critical')->count(),
            'services' => $entity->treasuryServices()->active()->get(['id', 'code', 'name', 'service_type']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function provinceRow(TreasuryEntity $entity): array
    {
        $missions = Mission::query()->where('treasury_entity_id', $entity->id)->count();

        return [
            'id' => $entity->id,
            'code' => $entity->code,
            'name' => $entity->name,
            'province' => $entity->province,
            'missions_count' => $missions,
        ];
    }
}
