<?php

namespace App\Services\Export;

use App\Models\Actif;
use App\Models\Mission;
use App\Models\Risque;
use Illuminate\Support\Collection;

/**
 * Codification TPMO (AC-ES-x / AC-SP-x, RSQ_*) pour les exports Excel.
 */
final class CartographyReferenceService
{
    /**
     * @return array{actifs: array<int, string>, risques: array<int, string>}
     */
    public function codesForMission(Mission $mission): array
    {
        $mission->loadMissing(['processus.actifs.risques']);

        $actifCodes = [];
        $riskCodes = [];
        $essentielSeq = 0;
        $supportSeq = 0;

        $processusList = $mission->processus->sortBy('id');

        foreach ($processusList as $processus) {
            foreach ($processus->actifs->sortBy('id') as $actif) {
                if ($actif->type === 'essentiel') {
                    $essentielSeq++;
                    $code = 'AC-ES-'.$essentielSeq;
                } else {
                    $supportSeq++;
                    $code = 'AC-SP-'.$supportSeq;
                }

                $actifCodes[$actif->id] = $code;

                foreach ($actif->risques->sortBy('id') as $risque) {
                    $riskCodes[$risque->id] = $this->riskReference($risque, $code);
                }
            }
        }

        return ['actifs' => $actifCodes, 'risques' => $riskCodes];
    }

    public function actifCode(Actif $actif, Mission $mission): string
    {
        $codes = $this->codesForMission($mission);

        return $codes['actifs'][$actif->id] ?? $this->fallbackActifCode($actif);
    }

    public function riskReference(Risque $risque, ?string $actifCode = null): string
    {
        if (filled($risque->risk_reference)) {
            return (string) $risque->risk_reference;
        }

        if ($actifCode !== null) {
            return 'RSQ_'.$actifCode;
        }

        return 'RSQ-'.$risque->id;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function cartographyRows(Mission $mission): Collection
    {
        $mission->loadMissing([
            'processus.actifs.risques.controles',
        ]);

        $codes = $this->codesForMission($mission);
        $rows = collect();

        foreach ($mission->processus->sortBy('id') as $processus) {
            foreach ($processus->actifs->sortBy('id') as $actif) {
                $actifCode = $codes['actifs'][$actif->id] ?? $this->fallbackActifCode($actif);
                $meta = is_array($actif->description) ? [] : [];

                if ($actif->risques->isEmpty()) {
                    $rows->push($this->blankRiskRow($actif, $actifCode, $processus->nom, $meta));

                    continue;
                }

                foreach ($actif->risques->sortBy('id') as $risque) {
                    $riskMeta = is_array($risque->metadata) ? $risque->metadata : [];
                    $controle = $risque->controles->first();

                    $rows->push([
                        'actif_code' => $actifCode,
                        'actif_type' => strtoupper($actif->type === 'essentiel' ? 'ESSENTIEL' : 'SUPPORT'),
                        'actif_nom' => $actif->nom,
                        'processus' => $processus->nom,
                        'gestionnaire' => $risque->proprietaire
                            ?? ($riskMeta['gestionnaire'] ?? ($meta['gestionnaire'] ?? '—')),
                        'objectifs' => $riskMeta['objectifs'] ?? $actif->description ?? '—',
                        'vulnerabilites' => $riskMeta['vulnerabilites'] ?? '—',
                        'menaces' => $riskMeta['menaces'] ?? '—',
                        'consequences' => $riskMeta['consequences'] ?? '—',
                        'risk_ref' => $codes['risques'][$risque->id] ?? $this->riskReference($risque, $actifCode),
                        'risk_label' => $risque->description,
                        'categorie' => $riskMeta['categorie'] ?? 'Risques Opérationnels',
                        'probabilite_inherent' => (int) ($risque->probabilite_inherent ?? 0),
                        'impact_inherent' => (int) ($risque->impact_inherent ?? 0),
                        'score_inherent' => (int) ($risque->score_inherent ?? 0),
                        'velocite' => $riskMeta['velocite'] ?? 'Immédiate',
                        'tendance' => $riskMeta['tendance'] ?? '—',
                        'controles' => $this->formatControles($risque),
                        'adequation' => $this->mapAdequation($controle?->efficacite),
                        'efficience' => $this->mapEfficience($controle?->efficacite),
                        'probabilite_residuel' => (int) ($risque->probabilite_residuel ?? 0),
                        'impact_residuel' => (int) ($risque->impact_residuel ?? 0),
                        'score_residuel' => (int) ($risque->score_residuel ?? 0),
                        'strategie_traiter' => $risque->treatment_plan ?? $risque->plan_mitigation ?? '—',
                        'strategie_transferer' => $riskMeta['strategie_transferer'] ?? '—',
                        'strategie_accepter' => $riskMeta['strategie_accepter'] ?? '—',
                    ]);
                }
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function blankRiskRow(Actif $actif, string $actifCode, string $processusNom, array $meta): array
    {
        return [
            'actif_code' => $actifCode,
            'actif_type' => strtoupper($actif->type === 'essentiel' ? 'ESSENTIEL' : 'SUPPORT'),
            'actif_nom' => $actif->nom,
            'processus' => $processusNom,
            'gestionnaire' => $meta['gestionnaire'] ?? '—',
            'objectifs' => $actif->description ?? '—',
            'vulnerabilites' => $meta['vulnerabilites'] ?? '—',
            'menaces' => $meta['menaces'] ?? '—',
            'consequences' => $meta['consequences'] ?? '—',
            'risk_ref' => '—',
            'risk_label' => '—',
            'categorie' => '—',
            'probabilite_inherent' => 0,
            'impact_inherent' => 0,
            'score_inherent' => 0,
            'velocite' => '—',
            'tendance' => '—',
            'controles' => '—',
            'adequation' => 0,
            'efficience' => 0,
            'probabilite_residuel' => 0,
            'impact_residuel' => 0,
            'score_residuel' => 0,
            'strategie_traiter' => '—',
            'strategie_transferer' => '—',
            'strategie_accepter' => '—',
        ];
    }

    private function fallbackActifCode(Actif $actif): string
    {
        $prefix = $actif->type === 'essentiel' ? 'AC-ES' : 'AC-SP';

        return $prefix.'-'.$actif->id;
    }

    private function formatControles(Risque $risque): string
    {
        if ($risque->controles->isEmpty()) {
            return 'Aucun contrôle';
        }

        return $risque->controles
            ->map(fn ($c) => trim(($c->description ?: $c->type).' ('.$c->efficacite.')'))
            ->implode("\n");
    }

    private function mapAdequation(?string $efficacite): int
    {
        return match ($efficacite) {
            'forte' => 2,
            'moyenne' => 1,
            'faible' => 0,
            default => 0,
        };
    }

    private function mapEfficience(?string $efficacite): int
    {
        return match ($efficacite) {
            'forte' => 2,
            'moyenne' => 1,
            'faible' => 0,
            default => 0,
        };
    }
}
