<?php

namespace App\Services\Risk;

use App\Models\Risque;

/**
 * Risque résiduel après application des contrôles (premier contrôle lié, logique métier historique).
 */
final class ResidualRiskCalculationService
{
    public function __construct(
        private CriticalityEvaluationService $criticality,
    ) {}

    public function apply(Risque $risque): void
    {
        $scoreInherent = (int) $risque->score_inherent;
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

        $scoreResiduel = (int) round($scoreInherent * $coef);

        $risque->score_residuel = $scoreResiduel;
        $risque->impact_residuel = $risque->impact_inherent;

        if ((int) $risque->impact_inherent > 0) {
            $risque->probabilite_residuel = (int) ceil($scoreResiduel / $risque->impact_inherent);
        }

        $risque->criticite_residuel = $this->criticality->levelFromScore($scoreResiduel)->value;

        $risque->saveQuietly();

        $risque->genererPlanActionAutomatique();

        if ($scoreResiduel >= 16) {
            $risque->loadMissing('actif.processus.mission');
            $mission = optional($risque->actif?->processus)->mission;
            if ($mission) {
                $mission->genererPlanAuditAutomatique();
            }
        }
    }
}
