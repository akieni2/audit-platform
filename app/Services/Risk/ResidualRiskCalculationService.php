<?php

namespace App\Services\Risk;

use App\Domain\Risk\Enums\CriticalityLevel;
use App\Models\Risque;

/**
 * Risque résiduel après application des contrôles (premier contrôle lié, logique métier historique).
 */
final class ResidualRiskCalculationService
{
    public function __construct(
        private RiskScoringService $scoring,
    ) {}

    public function apply(Risque $risque): void
    {
        $risque->load('controles');
        $controle = $risque->controles->first();

        if (! $controle) {
            return;
        }

        $coef = match ($controle->efficacite) {
            'faible' => 0.8,
            'moyenne' => 0.5,
            'forte' => 0.2,
            default => 1.0,
        };

        $residual = $this->scoring->packageResidualFromCoefficient(
            (int) $risque->impact_inherent,
            (int) $risque->probabilite_inherent,
            $coef,
        );

        $risque->score_residuel = $residual['score'];
        $risque->impact_residuel = $residual['impact'];
        $risque->probabilite_residuel = $residual['probability'];
        $risque->criticite_residuel = $residual['criticality'];

        $risque->saveQuietly();

        $risque->genererPlanActionAutomatique();

        if ($risque->criticite_residuel === CriticalityLevel::Critique->value) {
            $risque->loadMissing('actif.processus.mission');
            $mission = optional($risque->actif?->processus)->mission;
            if ($mission) {
                $mission->genererPlanAuditAutomatique();
            }
        }
    }
}
