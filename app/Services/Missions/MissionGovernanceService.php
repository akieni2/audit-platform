<?php

namespace App\Services\Missions;

use App\Domain\Missions\Events\MissionGovernanceTransitioned;
use App\Domain\Risk\Enums\CriticalityLevel;
use App\Models\Entretien;
use App\Models\IdentifiedRisk;
use App\Models\Mission;
use App\Models\MissionDocument;
use App\Models\MissionTeamMember;
use App\Models\User;
use App\Services\Risk\MissionRiskProjectionService;
use Illuminate\Support\Facades\Schema;

final class MissionGovernanceService
{
    public function __construct(
        private MissionWorkflowService $workflow,
        private MissionRiskProjectionService $riskProjections,
    ) {}

    /**
     * @return list<string>
     */
    public function allowedActions(User $actor, Mission $mission): array
    {
        return $this->workflow->allowedActions($actor, $mission);
    }

    public function transition(User $actor, Mission $mission, string $action, ?string $comment = null): Mission
    {
        $fromStatus = (string) ($mission->mission_status ?? '');
        $fresh = $this->workflow->transition($actor, $mission, $action, $comment);

        MissionGovernanceTransitioned::dispatch(
            $fresh,
            $actor,
            $action,
            $fromStatus,
            (string) ($fresh->mission_status ?? ''),
            $comment,
        );

        return $fresh;
    }

    /**
     * @return array{
     *   services_count:int,
     *   entretiens_total:int,
     *   entretiens_done:int,
     *   risks_count:int,
     *   risks_critical:int,
     *   official_risks_count:int,
     *   official_risks_critical:int,
     *   documents_count:int
     * }
     */
    public function missionStats(Mission $mission): array
    {
        $entretienStatusAvailable = Schema::hasColumn('entretiens', 'status');
        $missionDocumentsAvailable = Schema::hasTable('mission_documents');

        $projection = Schema::hasTable('mission_risk_projections')
            ? $this->riskProjections->refreshForMission($mission)
            : null;

        return [
            'services_count' => $mission->services()->count(),
            'entretiens_total' => Entretien::query()->where('mission_id', $mission->id)->count(),
            'entretiens_done' => $entretienStatusAvailable
                ? Entretien::query()
                    ->where('mission_id', $mission->id)
                    ->whereIn('status', [Entretien::STATUS_COMPLETED, Entretien::STATUS_VALIDATED])
                    ->count()
                : 0,
            'risks_count' => IdentifiedRisk::query()->where('mission_id', $mission->id)->count(),
            'risks_critical' => IdentifiedRisk::query()
                ->where('mission_id', $mission->id)
                ->where('criticality', CriticalityLevel::Critique->value)
                ->count(),
            'official_risks_count' => (int) ($projection?->official_count ?? 0),
            'official_risks_critical' => (int) ($projection?->official_critical_count ?? 0),
            'documents_count' => $missionDocumentsAvailable
                ? MissionDocument::query()->where('mission_id', $mission->id)->count()
                : 0,
        ];
    }

    public function missionProgressPercent(array $missionStats): ?int
    {
        $total = (int) ($missionStats['entretiens_total'] ?? 0);
        if ($total <= 0) {
            return null;
        }

        $done = (int) ($missionStats['entretiens_done'] ?? 0);

        return (int) min(100, max(0, (int) round(100 * $done / $total)));
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function eligibleTeamUsers(User $actor, Mission $mission)
    {
        if (! $actor->can('assignTeamMembers', $mission)) {
            return collect();
        }

        $existingIds = $mission->missionTeamMembers->pluck('user_id');

        return $mission->eligibleTeamUsers($actor)
            ->whereNotIn('id', $existingIds)
            ->values();
    }

    /**
     * @return array<string, string>
     */
    public function missionRoleLabels(): array
    {
        return MissionTeamMember::missionRoleLabels();
    }
}
