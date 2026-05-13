<?php

namespace App\Services\Risk;

use App\Domain\Risk\Enums\RiskLifecycleStatus;
use App\Domain\Risk\Enums\RiskStatus;
use App\Domain\Risk\Events\RiskPromoted;
use App\Models\Actif;
use App\Models\IdentifiedRisk;
use App\Models\Processus;
use App\Models\Risque;
use App\Models\User;
use App\Services\Runtime\BusinessEventLogger;
use App\Services\Runtime\CoreTransactionRunner;
use App\Services\Runtime\RuntimeMetricsService;

final class RiskPromotionService
{
    public function __construct(
        private RiskScoringService $scoring,
        private RiskLifecycleGuardService $lifecycle,
        private BusinessEventLogger $events,
        private RuntimeMetricsService $metrics,
        private CoreTransactionRunner $transactions,
    ) {}

    public function markReviewed(IdentifiedRisk $identifiedRisk, ?User $actor = null, ?string $notes = null): IdentifiedRisk
    {
        $identifiedRisk->loadMissing('mission', 'service.chefServiceUser');
        $this->lifecycle->ensureCanReview($identifiedRisk);

        $identifiedRisk->fill([
            'validated_by_human' => true,
            'reviewed_by' => $actor?->id,
            'reviewed_at' => now(),
            'lifecycle_status' => $this->isQualified($identifiedRisk)
                ? RiskLifecycleStatus::Qualified->value
                : RiskLifecycleStatus::Reviewed->value,
        ]);

        if ($notes !== null && trim($notes) !== '') {
            $identifiedRisk->promotion_notes = trim($notes);
        }

        $identifiedRisk->save();
        $reviewedAtTimestamp = $identifiedRisk->reviewed_at?->timestamp ?? now()->timestamp;

        $this->metrics->increment(
            metricKey: 'core_runtime.risk.reviewed',
            delta: 1,
            dimensions: ['lifecycle_status' => (string) $identifiedRisk->lifecycle_status],
            scopeType: 'mission',
            scopeId: $identifiedRisk->mission_id,
        );
        $this->events->record(
            eventName: 'core_runtime.risk.reviewed',
            payload: [
                'identified_risk_id' => $identifiedRisk->id,
                'lifecycle_status' => $identifiedRisk->lifecycle_status,
            ],
            context: [],
            aggregateType: 'identified_risk',
            aggregateId: $identifiedRisk->id,
            actor: $actor,
            missionId: $identifiedRisk->mission_id,
            idempotencyKey: 'risk-reviewed:'.$identifiedRisk->id.':'.$reviewedAtTimestamp,
        );

        return $identifiedRisk->fresh();
    }

    public function approve(IdentifiedRisk $identifiedRisk, ?User $actor = null, ?string $notes = null): IdentifiedRisk
    {
        if (! $identifiedRisk->validated_by_human) {
            $identifiedRisk = $this->markReviewed($identifiedRisk, $actor, $notes);
        }

        $this->lifecycle->ensureCanApprove($identifiedRisk);

        $identifiedRisk->fill([
            'approved_by' => $actor?->id,
            'approved_at' => now(),
            'lifecycle_status' => RiskLifecycleStatus::Approved->value,
        ]);

        if ($notes !== null && trim($notes) !== '') {
            $identifiedRisk->promotion_notes = trim($notes);
        }

        $identifiedRisk->save();
        $approvedAtTimestamp = $identifiedRisk->approved_at?->timestamp ?? now()->timestamp;

        $this->metrics->increment(
            metricKey: 'core_runtime.risk.approved',
            delta: 1,
            dimensions: ['lifecycle_status' => (string) $identifiedRisk->lifecycle_status],
            scopeType: 'mission',
            scopeId: $identifiedRisk->mission_id,
        );
        $this->events->record(
            eventName: 'core_runtime.risk.approved',
            payload: [
                'identified_risk_id' => $identifiedRisk->id,
                'lifecycle_status' => $identifiedRisk->lifecycle_status,
            ],
            context: [],
            aggregateType: 'identified_risk',
            aggregateId: $identifiedRisk->id,
            actor: $actor,
            missionId: $identifiedRisk->mission_id,
            idempotencyKey: 'risk-approved:'.$identifiedRisk->id.':'.$approvedAtTimestamp,
        );

        return $identifiedRisk->fresh();
    }

    public function promote(IdentifiedRisk $identifiedRisk, ?User $actor = null, ?string $notes = null): Risque
    {
        $identifiedRisk->loadMissing('mission.department', 'service.chefServiceUser', 'creator');

        if ($identifiedRisk->promotedRisk()->exists()) {
            /** @var Risque $existing */
            $existing = $identifiedRisk->promotedRisk()->firstOrFail();
            if ($identifiedRisk->lifecycle_status !== RiskLifecycleStatus::Promoted->value || $identifiedRisk->promoted_at === null) {
                $identifiedRisk->forceFill([
                    'lifecycle_status' => RiskLifecycleStatus::Promoted->value,
                    'promoted_at' => $identifiedRisk->promoted_at ?? now(),
                ])->save();
            }
            $this->metrics->increment(
                metricKey: 'core_runtime.risk.promote.idempotent',
                delta: 1,
                dimensions: ['reason' => 'existing_official_risk'],
                scopeType: 'mission',
                scopeId: $identifiedRisk->mission_id,
            );
            $this->events->record(
                eventName: 'core_runtime.risk.promote_idempotent',
                payload: [
                    'identified_risk_id' => $identifiedRisk->id,
                    'risque_id' => $existing->id,
                ],
                context: [],
                aggregateType: 'identified_risk',
                aggregateId: $identifiedRisk->id,
                actor: $actor,
                missionId: $identifiedRisk->mission_id,
                idempotencyKey: 'risk-promote-existing:'.$identifiedRisk->id,
                status: 'idempotent',
            );

            return $existing;
        }

        $this->lifecycle->ensureCanPromote($identifiedRisk);

        $correlationId = $this->events->resolveCorrelationId([
            'mission_id' => $identifiedRisk->mission_id,
            'identified_risk_id' => $identifiedRisk->id,
        ]);

        $result = $this->transactions->run(
            name: 'risk.promote',
            context: [
                'correlation_id' => $correlationId,
                'mission_id' => $identifiedRisk->mission_id,
                'identified_risk_id' => $identifiedRisk->id,
            ],
            callback: function ($transaction) use ($identifiedRisk, $actor, $notes, $correlationId) {
                $identifiedRisk = $this->approve($identifiedRisk, $actor, $notes);
                [$processus, $actif] = $this->resolveLegacyAnchors($identifiedRisk);

                $package = $this->scoring->packageInherent(
                    $identifiedRisk->probability,
                    $identifiedRisk->impact,
                    $identifiedRisk->criticality,
                );

                $risque = Risque::query()->firstOrNew([
                    'identified_risk_id' => $identifiedRisk->id,
                ]);

                $department = $identifiedRisk->mission?->department;
                $proprietaire = $identifiedRisk->service?->responsableDisplay()
                    ?? $identifiedRisk->creator?->displayName()
                    ?? 'À définir';

                $risque->fill([
                    'actif_id' => $actif->id,
                    'description' => $this->formatDescription($identifiedRisk),
                    'impact_inherent' => $package['impact'],
                    'probabilite_inherent' => $package['probability'],
                    'departement' => $department?->code ?? $department?->name,
                    'proprietaire' => $proprietaire,
                    'statut_risque' => $risque->statut_risque ?? RiskStatus::Identifie->value,
                    'criticite_inherent' => $package['criticality'],
                    'owner_department_id' => $identifiedRisk->mission?->department_id,
                    'source_department_id' => $identifiedRisk->mission?->department_id,
                    'severity' => $package['criticality'],
                    'lifecycle_status' => RiskLifecycleStatus::Promoted->value,
                ]);

                $risque->save();
                $risque->calculerRisqueResiduel();

                $identifiedRisk->fill([
                    'lifecycle_status' => RiskLifecycleStatus::Promoted->value,
                    'promoted_at' => now(),
                ]);

                if ($notes !== null && trim($notes) !== '') {
                    $identifiedRisk->promotion_notes = trim($notes);
                }

                $identifiedRisk->save();

                $freshResult = [
                    'identified_risk' => $identifiedRisk->fresh(),
                    'risque' => $risque->fresh(['actif.processus.mission']),
                ];

                $transaction->afterCommit(function () use ($freshResult, $correlationId): void {
                    RiskPromoted::dispatch(
                        $freshResult['identified_risk'],
                        $freshResult['risque'],
                        $correlationId,
                    );
                });

                return $freshResult;
            }
        );

        $this->metrics->increment(
            metricKey: 'core_runtime.risk.promoted',
            delta: 1,
            dimensions: ['criticality' => (string) $result['risque']->criticite_inherent],
            scopeType: 'mission',
            scopeId: $identifiedRisk->mission_id,
        );
        $this->events->record(
            eventName: 'core_runtime.risk.promoted',
            payload: [
                'identified_risk_id' => $result['identified_risk']->id,
                'risque_id' => $result['risque']->id,
                'criticality' => $result['risque']->criticite_inherent,
            ],
            context: ['correlation_id' => $correlationId],
            aggregateType: 'identified_risk',
            aggregateId: $result['identified_risk']->id,
            actor: $actor,
            missionId: $result['identified_risk']->mission_id,
            correlationId: $correlationId,
            idempotencyKey: 'risk-promoted:'.$result['identified_risk']->id,
        );

        return $result['risque'];
    }

    private function isQualified(IdentifiedRisk $identifiedRisk): bool
    {
        return filled($identifiedRisk->category)
            && filled($identifiedRisk->criticality)
            && (filled($identifiedRisk->probability) || filled($identifiedRisk->impact));
    }

    /**
     * @return array{Processus, Actif}
     */
    private function resolveLegacyAnchors(IdentifiedRisk $identifiedRisk): array
    {
        $mission = $identifiedRisk->mission;

        $processusName = $identifiedRisk->service?->nom
            ? 'Core intake - '.$identifiedRisk->service->nom
            : 'Core intake - mission #'.$identifiedRisk->mission_id;

        $processus = Processus::query()->firstOrCreate(
            [
                'mission_id' => $identifiedRisk->mission_id,
                'nom' => $processusName,
            ],
            [
                'description' => 'Processus technique de compatibilité pour les risques promus depuis le core enterprise.',
            ]
        );

        $actifName = $identifiedRisk->service?->nom
            ? 'Service - '.$identifiedRisk->service->nom
            : 'Mission - '.($mission?->reference ?: '#'.$identifiedRisk->mission_id);

        $actif = Actif::query()->firstOrCreate(
            [
                'processus_id' => $processus->id,
                'nom' => $actifName,
            ],
            [
                'type' => 'support',
                'description' => 'Actif de compatibilité créé pour porter un risque promu depuis un entretien/questionnaire.',
            ]
        );

        return [$processus, $actif];
    }

    private function formatDescription(IdentifiedRisk $identifiedRisk): string
    {
        $title = trim((string) $identifiedRisk->title);
        $description = trim((string) ($identifiedRisk->description ?? ''));

        if ($description === '') {
            return $title;
        }

        return $title.' — '.$description;
    }
}
