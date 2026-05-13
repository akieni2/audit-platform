<?php

namespace App\Services\Risk;

use App\Domain\Risk\Enums\RiskLifecycleStatus;
use App\Domain\Risk\Enums\RiskStatus;
use App\Domain\Risk\Events\RiskApproved;
use App\Domain\Risk\Events\RiskClosed;
use App\Domain\Risk\Events\RiskMitigated;
use App\Domain\Risk\Events\RiskPromoted;
use App\Domain\Risk\Events\RiskReviewed;
use App\Models\Actif;
use App\Models\Department;
use App\Models\IdentifiedRisk;
use App\Models\Processus;
use App\Models\Risque;
use App\Models\User;
use App\Services\Runtime\BusinessEventLogger;
use App\Services\Runtime\CoreTransactionRunner;
use App\Services\Runtime\RuntimeMetricsService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class RiskRegistryPromotionService
{
    public function __construct(
        private RiskScoringEngine $scoring,
        private RiskLifecycleGuardService $lifecycle,
        private BusinessEventLogger $events,
        private RuntimeMetricsService $metrics,
        private CoreTransactionRunner $transactions,
    ) {}

    public function submitForReview(IdentifiedRisk $identifiedRisk, ?User $actor = null, ?string $notes = null): IdentifiedRisk
    {
        $identifiedRisk->loadMissing('mission');
        $this->lifecycle->ensureCanSubmitForReview($identifiedRisk);

        $identifiedRisk->fill([
            'reviewed_by' => $actor?->id,
            'reviewed_at' => now(),
            'submitted_for_review_at' => now(),
            'lifecycle_status' => RiskLifecycleStatus::UnderReview->value,
        ]);

        if ($notes !== null && trim($notes) !== '') {
            $identifiedRisk->review_notes = trim($notes);
        }

        $identifiedRisk->save();

        $correlationId = $this->events->resolveCorrelationId([
            'mission_id' => $identifiedRisk->mission_id,
            'identified_risk_id' => $identifiedRisk->id,
        ]);

        RiskReviewed::dispatch(
            'identified_risk',
            $identifiedRisk->id,
            $identifiedRisk->mission_id,
            null,
            $actor?->id,
            $correlationId,
        );

        $this->metrics->increment(
            metricKey: 'core_runtime.risk.review.submitted',
            delta: 1,
            dimensions: ['lifecycle_status' => RiskLifecycleStatus::UnderReview->value],
            scopeType: 'mission',
            scopeId: $identifiedRisk->mission_id,
        );

        $this->events->record(
            eventName: 'core_runtime.risk.review_submitted',
            payload: [
                'identified_risk_id' => $identifiedRisk->id,
                'lifecycle_status' => $identifiedRisk->lifecycle_status,
            ],
            context: [],
            aggregateType: 'identified_risk',
            aggregateId: $identifiedRisk->id,
            actor: $actor,
            missionId: $identifiedRisk->mission_id,
            correlationId: $correlationId,
            idempotencyKey: 'risk-review-submitted:'.$identifiedRisk->id.':'.($identifiedRisk->reviewed_at?->timestamp ?? now()->timestamp),
        );

        return $identifiedRisk->fresh();
    }

    public function approve(IdentifiedRisk $identifiedRisk, ?User $actor = null, ?string $notes = null): IdentifiedRisk
    {
        $identifiedRisk->loadMissing('mission');
        $this->lifecycle->ensureCanApprove($identifiedRisk);

        if ($identifiedRisk->reviewed_at === null) {
            $identifiedRisk = $this->submitForReview($identifiedRisk, $actor, $notes);
        }

        $identifiedRisk->fill([
            'validated_by_human' => true,
            'approved_by' => $actor?->id,
            'approved_at' => now(),
            'lifecycle_status' => RiskLifecycleStatus::Validated->value,
        ]);

        if ($notes !== null && trim($notes) !== '') {
            $identifiedRisk->approval_notes = trim($notes);
            $identifiedRisk->promotion_notes = trim($notes);
        }

        $identifiedRisk->save();

        $correlationId = $this->events->resolveCorrelationId([
            'mission_id' => $identifiedRisk->mission_id,
            'identified_risk_id' => $identifiedRisk->id,
        ]);

        RiskApproved::dispatch(
            'identified_risk',
            $identifiedRisk->id,
            $identifiedRisk->mission_id,
            null,
            $actor?->id,
            $correlationId,
        );

        $this->metrics->increment(
            metricKey: 'core_runtime.risk.approved',
            delta: 1,
            dimensions: ['lifecycle_status' => RiskLifecycleStatus::Validated->value],
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
            correlationId: $correlationId,
            idempotencyKey: 'risk-approved:'.$identifiedRisk->id.':'.($identifiedRisk->approved_at?->timestamp ?? now()->timestamp),
        );

        return $identifiedRisk->fresh();
    }

    public function reject(IdentifiedRisk $identifiedRisk, ?User $actor = null, ?string $notes = null): IdentifiedRisk
    {
        $identifiedRisk->loadMissing('mission');
        $this->lifecycle->ensureCanReject($identifiedRisk);

        $identifiedRisk->fill([
            'rejected_by' => $actor?->id,
            'rejected_at' => now(),
            'lifecycle_status' => RiskLifecycleStatus::Rejected->value,
        ]);

        if ($notes !== null && trim($notes) !== '') {
            $identifiedRisk->rejection_notes = trim($notes);
        }

        $identifiedRisk->save();

        $this->metrics->increment(
            metricKey: 'core_runtime.risk.rejected',
            delta: 1,
            dimensions: ['lifecycle_status' => RiskLifecycleStatus::Rejected->value],
            scopeType: 'mission',
            scopeId: $identifiedRisk->mission_id,
        );

        $this->events->record(
            eventName: 'core_runtime.risk.rejected',
            payload: [
                'identified_risk_id' => $identifiedRisk->id,
                'lifecycle_status' => $identifiedRisk->lifecycle_status,
            ],
            context: [],
            aggregateType: 'identified_risk',
            aggregateId: $identifiedRisk->id,
            actor: $actor,
            missionId: $identifiedRisk->mission_id,
            idempotencyKey: 'risk-rejected:'.$identifiedRisk->id.':'.($identifiedRisk->rejected_at?->timestamp ?? now()->timestamp),
        );

        return $identifiedRisk->fresh();
    }

    public function promote(IdentifiedRisk $identifiedRisk, ?User $actor = null, ?string $notes = null): Risque
    {
        $identifiedRisk->loadMissing('mission.department', 'service.chefServiceUser', 'creator');

        $existing = Risque::query()
            ->where(function ($query) use ($identifiedRisk) {
                $query->where('source_identified_risk_id', $identifiedRisk->id)
                    ->orWhere('identified_risk_id', $identifiedRisk->id);
            })
            ->first();

        if ($existing !== null) {
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
                    'risk_uuid' => $existing->risk_uuid,
                ],
                context: [],
                aggregateType: 'identified_risk',
                aggregateId: $identifiedRisk->id,
                actor: $actor,
                missionId: $identifiedRisk->mission_id,
                idempotencyKey: 'risk-promote-existing:'.$identifiedRisk->id,
                status: 'idempotent',
            );

            return $existing->fresh();
        }

        $this->lifecycle->ensureCanPromote($identifiedRisk);

        $correlationId = $this->events->resolveCorrelationId([
            'mission_id' => $identifiedRisk->mission_id,
            'identified_risk_id' => $identifiedRisk->id,
        ]);

        $result = $this->transactions->run(
            name: 'risk.registry.promote',
            context: [
                'correlation_id' => $correlationId,
                'mission_id' => $identifiedRisk->mission_id,
                'identified_risk_id' => $identifiedRisk->id,
            ],
            callback: function ($transaction) use ($identifiedRisk, $actor, $notes, $correlationId) {
                $identifiedRisk = $this->approve($identifiedRisk, $actor, $notes);
                [$processus, $actif] = $this->resolveLegacyAnchors($identifiedRisk);

                $inherent = $this->scoring->inherent(
                    probability: $identifiedRisk->probability,
                    impact: $identifiedRisk->impact,
                    criticality: $identifiedRisk->criticality,
                );
                $residual = $this->scoring->residual(
                    probability: $identifiedRisk->probability,
                    impact: $identifiedRisk->impact,
                    controls: 0,
                    mitigation: data_get($identifiedRisk->metadata, 'mitigation_ratio'),
                    criticality: $identifiedRisk->criticality,
                );

                $department = $identifiedRisk->mission?->department;
                $ownerDepartmentId = $identifiedRisk->owner_department_id
                    ?? $identifiedRisk->mission?->department_id;
                $ownerUserId = $identifiedRisk->owner_user_id
                    ?? $identifiedRisk->service?->chefServiceUser?->id
                    ?? $identifiedRisk->created_by;
                $proprietaire = $identifiedRisk->service?->responsableDisplay()
                    ?? $identifiedRisk->creator?->displayName()
                    ?? 'À définir';

                $risque = Risque::query()->firstOrNew([
                    'source_identified_risk_id' => $identifiedRisk->id,
                ]);

                $risque->fill([
                    'actif_id' => $actif->id,
                    'identified_risk_id' => $identifiedRisk->id,
                    'source_identified_risk_id' => $identifiedRisk->id,
                    'source_entretien_id' => $identifiedRisk->entretien_id,
                    'source_question_id' => $identifiedRisk->questionnaire_question_id,
                    'description' => $this->formatDescription($identifiedRisk),
                    'proprietaire' => $proprietaire,
                    'departement' => $department?->code ?? $department?->name,
                    'owner_user_id' => $ownerUserId,
                    'owner_department_id' => $ownerDepartmentId,
                    'source_department_id' => $identifiedRisk->mission?->department_id,
                    'impact_inherent' => $inherent['impact'],
                    'probabilite_inherent' => $inherent['probability'],
                    'score_inherent' => $inherent['score'],
                    'criticite_inherent' => $inherent['criticality'],
                    'inherent_score' => $inherent['score'],
                    'impact_residuel' => $residual['impact'],
                    'probabilite_residuel' => $residual['probability'],
                    'score_residuel' => $residual['score'],
                    'criticite_residuel' => $residual['criticality'],
                    'residual_score' => $residual['score'],
                    'criticality' => $residual['criticality'],
                    'heatmap_x' => $inherent['heatmap_x'],
                    'heatmap_y' => $inherent['heatmap_y'],
                    'risk_uuid' => $risque->risk_uuid ?: (string) Str::uuid(),
                    'risk_reference' => $risque->risk_reference ?: $this->generateReference($identifiedRisk),
                    'promotion_signature' => $this->promotionSignature($identifiedRisk, $inherent, $residual),
                    'lifecycle_status' => RiskLifecycleStatus::Promoted->value,
                    'detected_at' => $risque->detected_at ?? $identifiedRisk->created_at ?? now(),
                    'reviewed_at' => $identifiedRisk->reviewed_at,
                    'promoted_at' => now(),
                    'reviewed_by' => $identifiedRisk->reviewed_by,
                    'promoted_by' => $actor?->id,
                    'approval_notes' => $identifiedRisk->approval_notes ?? $notes,
                    'metadata' => $this->buildRegistryMetadata(
                        identifiedRisk: $identifiedRisk,
                        processus: $processus,
                        actuatorId: $actor?->id,
                        notes: $notes,
                        promotionSignature: $this->promotionSignature($identifiedRisk, $inherent, $residual),
                    ),
                    'statut_risque' => $this->legacyStatusForLifecycle(RiskLifecycleStatus::Promoted),
                    'severity' => $residual['criticality'],
                ]);
                $risque->save();

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

                $transaction->afterCommit(function () use ($freshResult, $actor, $correlationId): void {
                    RiskPromoted::dispatch(
                        $freshResult['identified_risk'],
                        $freshResult['risque'],
                        $correlationId,
                        $actor?->id,
                        data_get($freshResult, 'risque.risk_uuid'),
                    );
                });

                return $freshResult;
            }
        );

        $this->metrics->increment(
            metricKey: 'core_runtime.risk.promoted',
            delta: 1,
            dimensions: ['criticality' => (string) $result['risque']->criticality],
            scopeType: 'mission',
            scopeId: $identifiedRisk->mission_id,
        );

        $this->events->record(
            eventName: 'core_runtime.risk.promoted',
            payload: [
                'identified_risk_id' => $result['identified_risk']->id,
                'risque_id' => $result['risque']->id,
                'risk_uuid' => $result['risque']->risk_uuid,
                'risk_reference' => $result['risque']->risk_reference,
                'criticality' => $result['risque']->criticality,
            ],
            context: ['correlation_id' => $correlationId],
            aggregateType: 'risk_registry',
            aggregateId: $result['risque']->risk_uuid ?? $result['risque']->id,
            actor: $actor,
            missionId: $result['identified_risk']->mission_id,
            correlationId: $correlationId,
            idempotencyKey: 'risk-promoted:'.$result['identified_risk']->id,
        );

        return $result['risque'];
    }

    public function mitigate(Risque $risque, ?User $actor = null, ?string $notes = null): Risque
    {
        $this->lifecycle->ensureCanMitigate($risque);

        $risque->fill([
            'lifecycle_status' => RiskLifecycleStatus::Mitigated->value,
            'statut_risque' => $this->legacyStatusForLifecycle(RiskLifecycleStatus::Mitigated),
        ]);

        $metadata = $risque->metadata ?? [];
        $metadata['mitigation'] = array_filter([
            'notes' => $notes,
            'actor_id' => $actor?->id,
            'at' => now()->toIso8601String(),
        ], fn ($value) => $value !== null && $value !== '');
        $risque->metadata = $metadata;
        $risque->save();

        RiskMitigated::dispatch(
            'risk_registry',
            $risque->risk_uuid ?? (string) $risque->id,
            $risque->mission_id,
            $risque->risk_uuid,
            $actor?->id,
        );

        $this->events->record(
            eventName: 'core_runtime.risk.mitigated',
            payload: [
                'risque_id' => $risque->id,
                'risk_uuid' => $risque->risk_uuid,
                'lifecycle_status' => $risque->lifecycle_status,
            ],
            context: [],
            aggregateType: 'risk_registry',
            aggregateId: $risque->risk_uuid ?? $risque->id,
            actor: $actor,
            missionId: $risque->mission_id,
            idempotencyKey: 'risk-mitigated:'.$risque->id.':'.$risque->updated_at?->timestamp,
        );

        return $risque->fresh();
    }

    public function close(Risque $risque, ?User $actor = null, ?string $notes = null): Risque
    {
        $this->lifecycle->ensureCanClose($risque);

        $risque->fill([
            'lifecycle_status' => RiskLifecycleStatus::Closed->value,
            'closed_at' => now(),
            'closure_notes' => $notes,
            'statut_risque' => $this->legacyStatusForLifecycle(RiskLifecycleStatus::Closed),
        ]);
        $risque->save();

        RiskClosed::dispatch(
            'risk_registry',
            $risque->risk_uuid ?? (string) $risque->id,
            $risque->mission_id,
            $risque->risk_uuid,
            $actor?->id,
        );

        $this->events->record(
            eventName: 'core_runtime.risk.closed',
            payload: [
                'risque_id' => $risque->id,
                'risk_uuid' => $risque->risk_uuid,
                'lifecycle_status' => $risque->lifecycle_status,
            ],
            context: [],
            aggregateType: 'risk_registry',
            aggregateId: $risque->risk_uuid ?? $risque->id,
            actor: $actor,
            missionId: $risque->mission_id,
            idempotencyKey: 'risk-closed:'.$risque->id.':'.($risque->closed_at?->timestamp ?? now()->timestamp),
        );

        return $risque->fresh();
    }

    public function archive(Risque $risque, ?User $actor = null, ?string $notes = null): Risque
    {
        $this->lifecycle->ensureCanArchive($risque);

        $risque->fill([
            'lifecycle_status' => RiskLifecycleStatus::Archived->value,
            'archived_at' => now(),
            'statut_risque' => $this->legacyStatusForLifecycle(RiskLifecycleStatus::Archived),
        ]);

        if ($notes !== null && trim($notes) !== '') {
            $metadata = $risque->metadata ?? [];
            $metadata['archive'] = [
                'notes' => trim($notes),
                'actor_id' => $actor?->id,
                'at' => now()->toIso8601String(),
            ];
            $risque->metadata = $metadata;
        }

        $risque->save();

        $this->events->record(
            eventName: 'core_runtime.risk.archived',
            payload: [
                'risque_id' => $risque->id,
                'risk_uuid' => $risque->risk_uuid,
                'lifecycle_status' => $risque->lifecycle_status,
            ],
            context: [],
            aggregateType: 'risk_registry',
            aggregateId: $risque->risk_uuid ?? $risque->id,
            actor: $actor,
            missionId: $risque->mission_id,
            idempotencyKey: 'risk-archived:'.$risque->id.':'.($risque->archived_at?->timestamp ?? now()->timestamp),
        );

        return $risque->fresh();
    }

    public function assignOwner(Risque $risque, ?int $ownerUserId, ?int $ownerDepartmentId = null): Risque
    {
        $risque->fill([
            'owner_user_id' => $ownerUserId,
            'owner_department_id' => $ownerDepartmentId ?? $risque->owner_department_id,
        ]);
        $risque->save();

        return $risque->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function ingestLegacySubmission(array $payload, ?User $actor = null): Risque
    {
        /** @var Actif $actif */
        $actif = Actif::query()
            ->with('processus.mission')
            ->findOrFail((int) $payload['actif_id']);

        $mission = $actif->processus?->mission;
        $description = trim((string) ($payload['description'] ?? ''));
        $signature = sha1(json_encode([
            'actif_id' => $actif->id,
            'description' => $description,
            'impact' => $payload['impact_inherent'] ?? null,
            'probability' => $payload['probabilite_inherent'] ?? null,
            'owner' => $payload['proprietaire'] ?? null,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');

        $identifiedRisk = IdentifiedRisk::query()->firstOrNew([
            'source_signature' => $signature,
        ]);

        $identifiedRisk->fill([
            'mission_id' => $mission?->id,
            'entretien_id' => null,
            'questionnaire_question_id' => null,
            'source_signature' => $signature,
            'title' => Str::limit($description, 160, ''),
            'description' => $description,
            'category' => 'legacy_risk_module',
            'probability' => (string) ($payload['probabilite_inherent'] ?? ''),
            'impact' => (string) ($payload['impact_inherent'] ?? ''),
            'criticality' => $this->scoring->canonicalCriticality((string) Arr::get($payload, 'criticality', '')),
            'recommendation' => $payload['plan_mitigation'] ?? null,
            'created_by' => $identifiedRisk->created_by ?? $actor?->id,
            'metadata' => array_filter([
                'legacy_module' => 'risques',
                'legacy_actif_id' => $actif->id,
                'legacy_proprietaire' => $payload['proprietaire'] ?? null,
                'legacy_departement' => $payload['departement'] ?? null,
                'legacy_status' => $payload['statut_risque'] ?? null,
            ], fn ($value) => $value !== null && $value !== ''),
        ]);
        $identifiedRisk->save();

        $identifiedRisk = $this->approve($identifiedRisk, $actor, 'Legacy risk module ingestion');

        $risque = $this->promote($identifiedRisk, $actor, 'Legacy risk module ingestion');
        $risque->forceFill([
            'actif_id' => $actif->id,
            'proprietaire' => $payload['proprietaire'] ?? $risque->proprietaire,
            'departement' => $payload['departement'] ?? $risque->departement,
            'date_revue' => $payload['date_revue'] ?? $risque->date_revue,
            'plan_mitigation' => $payload['plan_mitigation'] ?? $risque->plan_mitigation,
        ])->save();

        return $risque->fresh();
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

        return $title.' - '.$description;
    }

    private function generateReference(IdentifiedRisk $identifiedRisk): string
    {
        return sprintf(
            'RISK-%s-M%s-IR%s',
            now()->format('Y'),
            $identifiedRisk->mission_id ?: 'NA',
            $identifiedRisk->id ?: 'NA'
        );
    }

    /**
     * @param  array{probability:int, impact:int, score:int, criticality:string, heatmap_x:int, heatmap_y:int}  $inherent
     * @param  array{probability:int, impact:int, score:int, criticality:string, heatmap_x:int, heatmap_y:int}  $residual
     */
    private function promotionSignature(IdentifiedRisk $identifiedRisk, array $inherent, array $residual): string
    {
        return sha1(json_encode([
            'identified_risk_id' => $identifiedRisk->id,
            'source_signature' => $identifiedRisk->source_signature,
            'inherent' => $inherent,
            'residual' => $residual,
            'category' => $identifiedRisk->category,
            'criticality' => $identifiedRisk->criticality,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');
    }

    private function legacyStatusForLifecycle(RiskLifecycleStatus $status): string
    {
        return match ($status) {
            RiskLifecycleStatus::Detected => RiskStatus::Identifie->value,
            RiskLifecycleStatus::UnderReview, RiskLifecycleStatus::Validated => RiskStatus::EnAnalyse->value,
            RiskLifecycleStatus::Promoted => RiskStatus::Identifie->value,
            RiskLifecycleStatus::Mitigated => RiskStatus::Mitige->value,
            RiskLifecycleStatus::Closed, RiskLifecycleStatus::Archived => RiskStatus::Ferme->value,
            RiskLifecycleStatus::Rejected => RiskStatus::Accepte->value,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRegistryMetadata(
        IdentifiedRisk $identifiedRisk,
        Processus $processus,
        ?int $actuatorId,
        ?string $notes,
        string $promotionSignature,
    ): array {
        return array_filter([
            'source' => [
                'type' => 'identified_risk',
                'identified_risk_id' => $identifiedRisk->id,
                'source_signature' => $identifiedRisk->source_signature,
                'entretien_id' => $identifiedRisk->entretien_id,
                'question_id' => $identifiedRisk->questionnaire_question_id,
            ],
            'category' => $identifiedRisk->category,
            'recommendation' => $identifiedRisk->recommendation,
            'processus_id' => $processus->id,
            'actor_id' => $actuatorId,
            'notes' => $notes,
            'promotion_signature' => $promotionSignature,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
