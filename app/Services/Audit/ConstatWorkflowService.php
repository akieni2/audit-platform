<?php

namespace App\Services\Audit;

use App\Models\ActionCorrective;
use App\Models\AuditRecommendation;
use App\Models\Constat;
use App\Models\IdentifiedRisk;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class ConstatWorkflowService
{
    public function transition(Constat $constat, User $actor, string $action, ?string $comment = null): Constat
    {
        $map = [
            'submit_review' => [Constat::STATUS_DRAFT, Constat::STATUS_TEAM_REVIEW],
            'return_draft' => [Constat::STATUS_TEAM_REVIEW, Constat::STATUS_DRAFT],
            'validate_mission' => [Constat::STATUS_TEAM_REVIEW, Constat::STATUS_MISSION_VALIDATED],
            'open_contradictory' => [Constat::STATUS_MISSION_VALIDATED, Constat::STATUS_CONTRADICTORY],
            'finalize' => [Constat::STATUS_CONTRADICTORY, Constat::STATUS_FINAL],
        ];

        if (! isset($map[$action]) || $constat->status !== $map[$action][0]) {
            throw new DomainException('Cette transition n’est pas autorisée depuis l’état actuel du constat.');
        }

        return DB::transaction(function () use ($constat, $actor, $action, $comment, $map): Constat {
            $from = $constat->status;
            $to = $map[$action][1];
            $changes = ['status' => $to, 'version' => $constat->version + 1];
            if ($action === 'validate_mission') {
                $changes += ['validated_by' => $actor->id, 'validated_at' => now()];
            }
            if ($action === 'submit_review') {
                $changes += ['reviewed_by' => null, 'reviewed_at' => null];
            }
            $constat->update($changes);
            $constat->reviews()->create([
                'user_id' => $actor->id,
                'stage' => $from,
                'decision' => $action,
                'comment' => $comment,
                'snapshot' => $constat->fresh()->toArray(),
            ]);

            return $constat->fresh();
        });
    }

    public function recordTeamReview(Constat $constat, User $actor, string $decision, ?string $comment): Constat
    {
        if ($constat->status !== Constat::STATUS_TEAM_REVIEW) {
            throw new DomainException('Le constat n’est pas en revue collaborative.');
        }

        return DB::transaction(function () use ($constat, $actor, $decision, $comment): Constat {
            $constat->reviews()->create([
                'user_id' => $actor->id, 'stage' => 'team_review',
                'decision' => $decision, 'comment' => $comment,
                'snapshot' => $constat->toArray(),
            ]);
            $constat->update(['reviewed_by' => $actor->id, 'reviewed_at' => now()]);

            return $constat->fresh();
        });
    }

    public function convertToRisk(Constat $constat, User $actor): IdentifiedRisk
    {
        if ($constat->status !== Constat::STATUS_FINAL) {
            throw new DomainException('Seul un constat définitif peut devenir un risque.');
        }

        return DB::transaction(function () use ($constat, $actor): IdentifiedRisk {
            $risk = IdentifiedRisk::query()->firstOrCreate(
                ['constat_id' => $constat->id],
                [
                    'mission_id' => $constat->mission_id,
                    'service_id' => $constat->service_id,
                    'entretien_id' => $constat->response?->entretien_id,
                    'questionnaire_question_id' => $constat->questionnaire_question_id,
                    'source_signature' => hash('sha256', 'constat:'.$constat->id),
                    'title' => $constat->reference.' — '.str($constat->condition_observed ?: $constat->description)->limit(120),
                    'description' => $constat->condition_observed ?: $constat->description,
                    'criticality' => IdentifiedRisk::normalizeCriticality($constat->gravite),
                    'recommendation' => $constat->recommandation,
                    'lifecycle_status' => 'detected',
                    'validated_by_human' => false,
                    'created_by' => $actor->id,
                    'metadata' => ['constat_reference' => $constat->reference],
                ]
            );

            if ($constat->recommandation && ! $constat->recommendations()->exists()) {
                AuditRecommendation::query()->create([
                    'reference' => sprintf('REC-%06d', $constat->id),
                    'mission_id' => $constat->mission_id,
                    'constat_id' => $constat->id,
                    'identified_risk_id' => $risk->id,
                    'description' => $constat->recommandation,
                    'priority' => $constat->gravite ?: 'medium',
                    'status' => 'draft',
                    'created_by' => $actor->id,
                ]);
            }

            return $risk;
        });
    }

    public function createAction(AuditRecommendation $recommendation, array $data, User $actor): ActionCorrective
    {
        return DB::transaction(function () use ($recommendation, $data, $actor): ActionCorrective {
            $action = ActionCorrective::query()->create([
                'risque_id' => $recommendation->identifiedRisk?->promotedRisk?->id,
                'audit_recommendation_id' => $recommendation->id,
                'description' => $data['description'],
                'responsable' => $data['responsable'] ?? null,
                'owner_user_id' => $data['owner_user_id'] ?? null,
                'owner_department_id' => $data['owner_department_id'] ?? null,
                'date_echeance' => $data['date_echeance'] ?? null,
                'statut' => 'ouvert',
                'progress_percent' => 0,
            ]);
            $action->updates()->create(['user_id' => $actor->id, 'progress_percent' => 0, 'status' => 'ouvert', 'comment' => 'Action créée.']);

            return $action;
        });
    }
}
