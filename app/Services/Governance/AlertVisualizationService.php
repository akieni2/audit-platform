<?php

namespace App\Services\Governance;

class AlertVisualizationService
{
    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<int, array<string, mixed>>
     */
    public function build(array $snapshot): array
    {
        $alerts = [];
        $complianceRate = (float) data_get($snapshot, 'intelligence.maturity.compliance_rate', 0);
        $recurring = collect(data_get($snapshot, 'intelligence.recurring', []));

        if ($complianceRate < 60) {
            $alerts[] = [
                'tone' => 'danger',
                'title' => 'Conformité sous seuil',
                'message' => 'Le taux de conformité consolidé est sous 60%.',
            ];
        }

        if ($recurring->count() > 0) {
            $first = $recurring->first();
            $alerts[] = [
                'tone' => 'warning',
                'title' => 'Risque récurrent prioritaire',
                'message' => data_get($first, 'label', 'Un cluster récurrent nécessite un suivi renforcé.'),
            ];
        }

        if ($alerts === []) {
            $alerts[] = [
                'tone' => 'success',
                'title' => 'Alerte maîtrisée',
                'message' => 'Aucune alerte critique détectée sur la consolidation actuelle.',
            ];
        }

        return $alerts;
    }
}
