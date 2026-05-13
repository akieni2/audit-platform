<?php

namespace App\Services\Risk;

use App\Domain\Risk\Enums\CriticalityLevel;
use App\Domain\Risk\Enums\RiskLifecycleStatus;
use App\Models\IdentifiedRisk;
use App\Models\Risque;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class RiskRegistryQueryService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Risque>
     */
    public function officialQuery(array $filters = []): Builder
    {
        $query = Risque::query()
            ->with(['actif.processus.mission', 'sourceIdentifiedRisk.service', 'ownerDepartment', 'owner']);

        if (($missionId = $filters['mission_id'] ?? null) !== null) {
            $query->whereHas('actif.processus', fn (Builder $q) => $q->where('mission_id', $missionId));
        }

        if (($departmentId = $filters['department_id'] ?? null) !== null) {
            $query->where(function (Builder $q) use ($departmentId) {
                $q->where('owner_department_id', $departmentId)
                    ->orWhere('source_department_id', $departmentId)
                    ->orWhereHas('actif.processus.mission', fn (Builder $mq) => $mq->where('department_id', $departmentId));
            });
        }

        if (($lifecycle = $filters['lifecycle_status'] ?? null) !== null) {
            $query->where('lifecycle_status', RiskLifecycleStatus::fromMixed((string) $lifecycle)->value);
        }

        if (($criticality = $filters['criticality'] ?? null) !== null) {
            $normalized = CriticalityLevel::fromMixed((string) $criticality)?->value;
            if ($normalized !== null) {
                $query->where('criticality', $normalized);
            }
        }

        if (($ownerUserId = $filters['owner_user_id'] ?? null) !== null) {
            $query->where('owner_user_id', $ownerUserId);
        }

        return $query->orderByDesc('residual_score')->orderByDesc('score_inherent');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<IdentifiedRisk>
     */
    public function intakeQuery(array $filters = []): Builder
    {
        $query = IdentifiedRisk::query()
            ->with(['mission.department', 'service', 'ownerDepartment', 'owner']);

        if (($missionId = $filters['mission_id'] ?? null) !== null) {
            $query->where('mission_id', $missionId);
        }

        if (($departmentId = $filters['department_id'] ?? null) !== null) {
            $query->where(function (Builder $q) use ($departmentId) {
                $q->where('owner_department_id', $departmentId)
                    ->orWhereHas('mission', fn (Builder $mq) => $mq->where('department_id', $departmentId));
            });
        }

        if (($lifecycle = $filters['lifecycle_status'] ?? null) !== null) {
            $query->where('lifecycle_status', RiskLifecycleStatus::fromMixed((string) $lifecycle)->value);
        }

        if (($criticality = $filters['criticality'] ?? null) !== null) {
            $normalized = CriticalityLevel::fromMixed((string) $criticality)?->value;
            if ($normalized !== null) {
                $query->where('criticality', $normalized);
            }
        }

        if (($ownerUserId = $filters['owner_user_id'] ?? null) !== null) {
            $query->where('owner_user_id', $ownerUserId);
        }

        return $query->orderByDesc('updated_at');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function lifecycleBreakdown(array $filters = []): array
    {
        $breakdown = [];

        foreach (RiskLifecycleStatus::cases() as $status) {
            $breakdown[$status->value] = 0;
        }

        foreach ($this->officialQuery($filters)->get(['lifecycle_status']) as $risk) {
            $status = RiskLifecycleStatus::fromMixed((string) $risk->lifecycle_status)->value;
            $breakdown[$status] = ($breakdown[$status] ?? 0) + 1;
        }

        foreach ($this->intakeQuery($filters)->get(['lifecycle_status']) as $risk) {
            $status = RiskLifecycleStatus::fromMixed((string) $risk->lifecycle_status)->value;
            $breakdown[$status] = ($breakdown[$status] ?? 0) + 1;
        }

        return $breakdown;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function criticalityBreakdown(array $filters = []): array
    {
        $breakdown = [
            CriticalityLevel::Low->value => 0,
            CriticalityLevel::Medium->value => 0,
            CriticalityLevel::High->value => 0,
            CriticalityLevel::Critical->value => 0,
        ];

        foreach ($this->officialQuery($filters)->get(['criticality', 'criticite_residuel', 'criticite_inherent']) as $risk) {
            $value = CriticalityLevel::fromMixed($risk->criticality ?: $risk->criticite_residuel ?: $risk->criticite_inherent)?->value;
            if ($value !== null) {
                $breakdown[$value] = ($breakdown[$value] ?? 0) + 1;
            }
        }

        foreach ($this->intakeQuery($filters)->get(['criticality']) as $risk) {
            $value = CriticalityLevel::fromMixed($risk->criticality)?->value;
            if ($value !== null) {
                $breakdown[$value] = ($breakdown[$value] ?? 0) + 1;
            }
        }

        return $breakdown;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{critical_open:int, in_review:int, promoted:int, mitigated:int, residual_exposure:int, total_registry:int, total_intake:int}
     */
    public function kpis(array $filters = []): array
    {
        $official = $this->officialQuery($filters)->get();
        $intake = $this->intakeQuery($filters)->get();

        $openOfficial = $official->reject(fn (Risque $risk) => in_array(
            RiskLifecycleStatus::fromMixed($risk->lifecycle_status)->value,
            [
                RiskLifecycleStatus::Closed->value,
                RiskLifecycleStatus::Archived->value,
                RiskLifecycleStatus::Rejected->value,
            ],
            true
        ));

        $criticalOpen = $openOfficial->filter(function (Risque $risk): bool {
            return CriticalityLevel::fromMixed($risk->criticality ?: $risk->criticite_residuel ?: $risk->criticite_inherent) === CriticalityLevel::Critical;
        })->count();

        return [
            'critical_open' => $criticalOpen,
            'in_review' => $intake->where('lifecycle_status', RiskLifecycleStatus::UnderReview->value)->count(),
            'promoted' => $official->where('lifecycle_status', RiskLifecycleStatus::Promoted->value)->count(),
            'mitigated' => $official->where('lifecycle_status', RiskLifecycleStatus::Mitigated->value)->count(),
            'residual_exposure' => (int) $official->sum(fn (Risque $risk) => (int) ($risk->residual_score ?? $risk->score_residuel ?? 0)),
            'total_registry' => $official->count(),
            'total_intake' => $intake->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function trends(array $filters = [], int $monthsBack = 12): array
    {
        $official = $this->officialQuery($filters)
            ->where('created_at', '>=', now()->subMonths($monthsBack)->startOfMonth())
            ->get(['created_at']);
        $intake = $this->intakeQuery($filters)
            ->where('created_at', '>=', now()->subMonths($monthsBack)->startOfMonth())
            ->get(['created_at']);

        $out = [];

        foreach ($official->concat($intake) as $risk) {
            $ym = optional($risk->created_at)->format('Y-m');
            if ($ym === null) {
                continue;
            }

            $out[$ym] = ($out[$ym] ?? 0) + 1;
        }

        ksort($out);

        return $out;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function risksByDepartment(array $filters = []): array
    {
        $out = [];

        foreach ($this->officialQuery($filters)->get() as $risk) {
            $label = $risk->ownerDepartment?->code
                ?? $risk->ownerDepartment?->name
                ?? $risk->departement
                ?? 'Non renseigné';
            $out[$label] = ($out[$label] ?? 0) + 1;
        }

        arsort($out);

        return $out;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function topServicesExposed(array $filters = [], int $limit = 5): array
    {
        $scores = [];

        foreach ($this->officialQuery($filters)->get() as $risk) {
            $label = $risk->sourceIdentifiedRisk?->service?->nom
                ?? $risk->actif?->nom
                ?? 'Service non rattaché';
            $scores[$label] = ($scores[$label] ?? 0) + (int) ($risk->residual_score ?? $risk->score_residuel ?? $risk->score_inherent ?? 0);
        }

        arsort($scores);

        return array_slice($scores, 0, $limit, true);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Risque>
     */
    public function registry(array $filters = []): Collection
    {
        return $this->officialQuery($filters)->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, IdentifiedRisk>
     */
    public function intake(array $filters = []): Collection
    {
        return $this->intakeQuery($filters)->get();
    }
}
