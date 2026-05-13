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
use Illuminate\Support\Facades\DB;

final class RiskPromotionService
{
    public function __construct(
        private RiskScoringService $scoring,
    ) {}

    public function markReviewed(IdentifiedRisk $identifiedRisk, ?User $actor = null, ?string $notes = null): IdentifiedRisk
    {
        $identifiedRisk->loadMissing('mission', 'service.chefServiceUser');

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

        return $identifiedRisk->fresh();
    }

    public function approve(IdentifiedRisk $identifiedRisk, ?User $actor = null, ?string $notes = null): IdentifiedRisk
    {
        if (! $identifiedRisk->validated_by_human) {
            $identifiedRisk = $this->markReviewed($identifiedRisk, $actor, $notes);
        }

        $identifiedRisk->fill([
            'approved_by' => $actor?->id,
            'approved_at' => now(),
            'lifecycle_status' => RiskLifecycleStatus::Approved->value,
        ]);

        if ($notes !== null && trim($notes) !== '') {
            $identifiedRisk->promotion_notes = trim($notes);
        }

        $identifiedRisk->save();

        return $identifiedRisk->fresh();
    }

    public function promote(IdentifiedRisk $identifiedRisk, ?User $actor = null, ?string $notes = null): Risque
    {
        $identifiedRisk->loadMissing('mission.department', 'service.chefServiceUser', 'creator');

        $result = DB::transaction(function () use ($identifiedRisk, $actor, $notes) {
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

            return [
                'identified_risk' => $identifiedRisk->fresh(),
                'risque' => $risque->fresh(['actif.processus.mission']),
            ];
        });

        RiskPromoted::dispatch($result['identified_risk'], $result['risque']);

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
