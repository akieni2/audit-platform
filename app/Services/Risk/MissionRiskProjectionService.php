<?php

namespace App\Services\Risk;

use App\Domain\Risk\Enums\CriticalityLevel;
use App\Domain\Risk\Enums\RiskLifecycleStatus;
use App\Models\IdentifiedRisk;
use App\Models\Mission;
use App\Models\MissionRiskProjection;
use App\Models\Risque;
use App\Services\Runtime\BusinessEventLogger;
use App\Services\Runtime\CoreTransactionRunner;
use App\Services\Runtime\RuntimeMetricsService;
use Illuminate\Support\Facades\Schema;

final class MissionRiskProjectionService
{
    public function __construct(
        private HeatmapProjectionService $heatmaps,
        private ProjectionIntegrityService $integrity,
        private BusinessEventLogger $events,
        private RuntimeMetricsService $metrics,
        private CoreTransactionRunner $transactions,
    ) {}

    public function refreshForMission(Mission $mission): MissionRiskProjection
    {
        return $this->refreshForMissionId((int) $mission->id);
    }

    /**
     * @return array{
     *   counts: array<string, int>,
     *   heatmaps: array{inherent: array<string, int>, residual: array<string, int>},
     *   signature: string,
     *   source_record_count: int
     * }
     */
    public function computeSnapshot(int $missionId): array
    {
        $intake = IdentifiedRisk::query()
            ->where('mission_id', $missionId)
            ->get(['lifecycle_status']);

        $official = Risque::query()
            ->whereHas('actif.processus', fn ($q) => $q->where('mission_id', $missionId));

        $inherentHeatmap = $this->heatmaps->inherentForMission($missionId);
        $residualHeatmap = $this->heatmaps->residualForMission($missionId);

        $counts = [
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
        ];

        $snapshot = [
            'counts' => $counts,
            'heatmaps' => [
                'inherent' => $inherentHeatmap['counts'],
                'residual' => $residualHeatmap['counts'],
            ],
            'source_record_count' => array_sum($counts),
        ];

        $snapshot['signature'] = sha1(json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');

        return $snapshot;
    }

    public function refreshForMissionId(int $missionId, bool $force = false, ?string $correlationId = null): MissionRiskProjection
    {
        $snapshot = $this->computeSnapshot($missionId);
        if (! Schema::hasTable('mission_risk_projections')) {
            return new MissionRiskProjection([
                'mission_id' => $missionId,
                ...$snapshot['counts'],
                'inherent_heatmap' => $snapshot['heatmaps']['inherent'],
                'residual_heatmap' => $snapshot['heatmaps']['residual'],
                'source_signature' => $snapshot['signature'],
                'source_record_count' => $snapshot['source_record_count'],
                'integrity_status' => 'unavailable',
            ]);
        }

        $existing = MissionRiskProjection::query()->where('mission_id', $missionId)->first();

        if (! $force && $existing !== null && $existing->source_signature === $snapshot['signature']) {
            $this->metrics->increment(
                metricKey: 'core_runtime.projection.refresh.skipped',
                delta: 1,
                dimensions: ['projection_type' => 'mission_risk_projection'],
                scopeType: 'mission',
                scopeId: $missionId,
            );

            $this->events->record(
                eventName: 'core_runtime.projection.refresh_skipped',
                payload: ['projection_type' => 'mission_risk_projection', 'source_signature' => $snapshot['signature']],
                context: ['correlation_id' => $correlationId],
                aggregateType: 'mission_risk_projection',
                aggregateId: $missionId,
                missionId: $missionId,
                correlationId: $correlationId,
                idempotencyKey: 'projection-refresh-skipped:'.$missionId.':'.$snapshot['signature'],
                status: 'idempotent',
            );

            $this->integrity->verifyMissionRiskProjection($missionId, $snapshot, $existing, false, $correlationId);

            return $existing;
        }

        /** @var MissionRiskProjection $projection */
        $projection = $this->transactions->run(
            name: 'risk_projection.refresh',
            context: ['correlation_id' => $correlationId, 'mission_id' => $missionId],
            callback: function ($transaction) use ($missionId, $snapshot, $correlationId) {
                $projection = MissionRiskProjection::query()->firstOrNew(['mission_id' => $missionId]);
                $attributes = [
                    ...$snapshot['counts'],
                    'inherent_heatmap' => $snapshot['heatmaps']['inherent'],
                    'residual_heatmap' => $snapshot['heatmaps']['residual'],
                    'refreshed_at' => now(),
                ];

                if (Schema::hasColumn('mission_risk_projections', 'source_signature')) {
                    $attributes['source_signature'] = $snapshot['signature'];
                }
                if (Schema::hasColumn('mission_risk_projections', 'source_record_count')) {
                    $attributes['source_record_count'] = $snapshot['source_record_count'];
                }
                if (Schema::hasColumn('mission_risk_projections', 'refresh_count')) {
                    $attributes['refresh_count'] = (int) ($projection->refresh_count ?? 0) + 1;
                }
                if (Schema::hasColumn('mission_risk_projections', 'integrity_status')) {
                    $attributes['integrity_status'] = 'pending';
                }

                $projection->fill($attributes);
                $projection->save();

                return $projection->fresh();
            }
        );

        $this->metrics->increment(
            metricKey: 'core_runtime.projection.refresh.executed',
            delta: 1,
            dimensions: ['projection_type' => 'mission_risk_projection'],
            scopeType: 'mission',
            scopeId: $missionId,
        );

        $this->events->record(
            eventName: 'core_runtime.projection.refreshed',
            payload: [
                'projection_type' => 'mission_risk_projection',
                'source_signature' => $snapshot['signature'],
                'source_record_count' => $snapshot['source_record_count'],
            ],
            context: ['correlation_id' => $correlationId],
            aggregateType: 'mission_risk_projection',
            aggregateId: $missionId,
            missionId: $missionId,
            correlationId: $correlationId,
            idempotencyKey: 'projection-refresh:'.$missionId.':'.$snapshot['signature'],
        );

        $this->integrity->verifyMissionRiskProjection(
            missionId: $missionId,
            expectedSnapshot: $snapshot,
            existingProjection: $projection->fresh(),
            repaired: $force,
            correlationId: $correlationId,
        );

        return $projection->fresh();
    }
}
