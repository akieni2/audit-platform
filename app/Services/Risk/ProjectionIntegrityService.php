<?php

namespace App\Services\Risk;

use App\Models\MissionRiskProjection;
use App\Models\ProjectionIntegrityCheck;
use App\Services\Runtime\BusinessEventLogger;
use App\Services\Runtime\RuntimeMetricsService;
use Illuminate\Support\Facades\Schema;

final class ProjectionIntegrityService
{
    public function __construct(
        private BusinessEventLogger $events,
        private RuntimeMetricsService $metrics,
    ) {}

    public function verifyMissionRiskProjection(
        int $missionId,
        array $expectedSnapshot,
        ?MissionRiskProjection $existingProjection = null,
        bool $repaired = false,
        ?string $correlationId = null,
    ): ProjectionIntegrityCheck {
        $actualSnapshot = $existingProjection === null
            ? null
            : [
                'signature' => $existingProjection->source_signature,
                'counts' => [
                    'intake_detected_count' => (int) $existingProjection->intake_detected_count,
                    'intake_reviewed_count' => (int) $existingProjection->intake_reviewed_count,
                    'intake_qualified_count' => (int) $existingProjection->intake_qualified_count,
                    'intake_approved_count' => (int) $existingProjection->intake_approved_count,
                    'intake_promoted_count' => (int) $existingProjection->intake_promoted_count,
                    'official_count' => (int) $existingProjection->official_count,
                    'official_critical_count' => (int) $existingProjection->official_critical_count,
                    'official_residual_critical_count' => (int) $existingProjection->official_residual_critical_count,
                ],
                'heatmaps' => [
                    'inherent' => $existingProjection->inherent_heatmap ?? [],
                    'residual' => $existingProjection->residual_heatmap ?? [],
                ],
            ];

        $status = $existingProjection === null
            ? 'missing_projection'
            : (($actualSnapshot['signature'] ?? null) === ($expectedSnapshot['signature'] ?? null) ? 'ok' : 'mismatch');

        if ($repaired && $status === 'mismatch') {
            $status = 'repaired';
        }

        $mismatchCount = $status === 'ok'
            ? 0
            : $this->mismatchCount($expectedSnapshot, $actualSnapshot);

        $check = new ProjectionIntegrityCheck([
            'projection_type' => 'mission_risk_projection',
            'scope_type' => 'mission',
            'scope_id' => (string) $missionId,
            'status' => $status,
            'correlation_id' => $correlationId,
            'expected_signature' => $expectedSnapshot['signature'] ?? null,
            'actual_signature' => $actualSnapshot['signature'] ?? null,
            'mismatch_count' => $mismatchCount,
            'expected_payload' => $expectedSnapshot,
            'actual_payload' => $actualSnapshot,
            'checked_at' => now(),
        ]);

        if (Schema::hasTable('projection_integrity_checks')) {
            $check = ProjectionIntegrityCheck::query()->create($check->getAttributes());
        }

        if ($existingProjection !== null && Schema::hasTable('mission_risk_projections')) {
            $updates = [];
            if (Schema::hasColumn('mission_risk_projections', 'integrity_status')) {
                $updates['integrity_status'] = $status === 'ok' ? 'ok' : ($repaired ? 'repaired' : 'mismatch');
            }
            if (Schema::hasColumn('mission_risk_projections', 'last_integrity_checked_at')) {
                $updates['last_integrity_checked_at'] = now();
            }
            if ($updates !== []) {
                $existingProjection->forceFill($updates)->save();
            }
        }

        $this->metrics->increment(
            metricKey: 'core_runtime.projection.integrity.'.$status,
            delta: 1,
            dimensions: ['projection_type' => 'mission_risk_projection'],
            scopeType: 'mission',
            scopeId: $missionId,
        );

        $this->events->record(
            eventName: 'core_runtime.projection.integrity_checked',
            payload: [
                'projection_type' => 'mission_risk_projection',
                'status' => $status,
                'mismatch_count' => $mismatchCount,
            ],
            context: ['correlation_id' => $correlationId],
            aggregateType: 'mission_risk_projection',
            aggregateId: $missionId,
            missionId: $missionId,
            correlationId: $correlationId,
        );

        return $check;
    }

    /**
     * @param  array<string, mixed>|null  $actualSnapshot
     */
    private function mismatchCount(array $expectedSnapshot, ?array $actualSnapshot): int
    {
        if ($actualSnapshot === null) {
            return 1;
        }

        $count = 0;
        if (($expectedSnapshot['signature'] ?? null) !== ($actualSnapshot['signature'] ?? null)) {
            $count++;
        }
        if (($expectedSnapshot['counts'] ?? []) !== ($actualSnapshot['counts'] ?? [])) {
            $count++;
        }
        if (($expectedSnapshot['heatmaps'] ?? []) !== ($actualSnapshot['heatmaps'] ?? [])) {
            $count++;
        }

        return $count;
    }
}
