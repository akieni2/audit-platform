<?php

namespace App\Services\Dgcpt;

use App\Models\Mission;
use App\Models\Risque;
use Illuminate\Support\Collection;

/**
 * Enrichit le contexte du copilote IA avec la hiérarchie DGCPT (phase 8).
 */
final class DgcptCopilotContextService
{
    public function __construct(
        private DgcptHierarchyService $hierarchy,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildForMission(?Mission $mission): array
    {
        if ($mission === null) {
            return ['dgcpt' => null];
        }

        return [
            'dgcpt' => [
                'mission_context' => $this->hierarchy->missionContext($mission),
                'recurring_patterns' => $this->recurringRiskPatterns($mission),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function recurringRiskPatterns(Mission $mission): array
    {
        $mission->loadMissing('treasuryEntity', 'treasuryService', 'auditDomain');

        $patterns = [];

        if ($mission->treasuryService?->service_type === 'informatique') {
            $weakCount = Risque::query()
                ->whereHas('actif.processus.mission', function ($q) use ($mission) {
                    $q->where('treasury_service_id', $mission->treasury_service_id);
                })
                ->where('score_residuel', '>=', 12)
                ->count();

            if ($weakCount > 0) {
                $patterns[] = 'Le service informatique présente des risques résiduels élevés sur cette mission.';
            }
        }

        if ($mission->auditDomain?->code === 'SAUVEGARDE') {
            $patterns[] = 'Les risques liés aux sauvegardes sont un focus du domaine d\'audit.';
        }

        $province = $mission->treasuryEntity?->province;
        if ($province) {
            $peerMissions = Mission::query()
                ->whereHas('treasuryEntity', fn ($q) => $q->where('province', $province))
                ->whereKeyNot($mission->id)
                ->count();

            if ($peerMissions > 0) {
                $patterns[] = "D'autres missions existent dans la province {$province} pour comparaison.";
            }
        }

        return $patterns;
    }
}
