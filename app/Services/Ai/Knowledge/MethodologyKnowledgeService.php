<?php

namespace App\Services\Ai\Knowledge;

use App\Models\MethodologyTemplate;
use App\Services\Methodologies\DgcptAuditProcedureGenerator;

class MethodologyKnowledgeService
{
    public function __construct(
        private DgcptAuditProcedureGenerator $procedureGenerator,
    ) {}

    /**
     * @return array<string, list<string>>
     */
    public function frameworks(): array
    {
        $catalog = MethodologyTemplate::query()
            ->where('active', true)
            ->where('lifecycle_status', MethodologyTemplate::STATUS_PUBLISHED)
            ->orderBy('framework_key')
            ->get();

        if ($catalog->isEmpty()) {
            return $this->fallbackFrameworks();
        }

        return $catalog->mapWithKeys(function (MethodologyTemplate $template): array {
            $procedure = $this->procedureGenerator->generate($template);

            return [
                $template->framework_key => collect($procedure['stages'])
                    ->pluck('code')
                    ->filter()
                    ->values()
                    ->all(),
            ];
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function procedureFor(string $framework): array
    {
        $template = MethodologyTemplate::query()
            ->where('active', true)
            ->where(function ($query) use ($framework) {
                $query->where('framework_key', $framework)
                    ->orWhere('code', $framework)
                    ->orWhere('slug', $framework);
            })
            ->first();

        if ($template === null) {
            return [
                'referential' => ['framework_key' => $framework],
                'stages' => [],
                'deliverables' => [],
                'questions' => [],
                'risk_families' => [],
                'taxonomy_terms' => [],
            ];
        }

        return $this->procedureGenerator->generate($template);
    }

    /**
     * @return list<string>
     */
    public function controlsFor(string $framework): array
    {
        return $this->frameworks()[$framework] ?? [];
    }

    /**
     * @return list<array<string, string>>
     */
    public function questionsFor(string $framework): array
    {
        return $this->procedureFor($framework)['questions'] ?? [];
    }

    /**
     * @return list<string>
     */
    public function deliverablesFor(string $framework): array
    {
        return $this->procedureFor($framework)['deliverables'] ?? [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function riskFamiliesFor(string $framework): array
    {
        return $this->procedureFor($framework)['risk_families'] ?? [];
    }

    /**
     * @return array<string, list<string>>
     */
    private function fallbackFrameworks(): array
    {
        return [
            'ISACA' => ['LANCEMENT', 'PREPARATION', 'TERRAIN', 'CARTOGRAPHIE', 'REPORTING'],
            'ITAF' => ['LANCEMENT', 'PREPARATION', 'TERRAIN', 'CARTOGRAPHIE', 'REPORTING'],
            'ISO19011' => ['CADRAGE', 'REFERENTIEL', 'EXECUTION', 'RISQUES', 'SUIVI'],
            'ISO38500' => ['CADRAGE', 'REFERENTIEL', 'EXECUTION', 'RISQUES', 'SUIVI'],
            'COBIT' => ['EDM', 'APO', 'BAI', 'DSS', 'MEA'],
            'ISO31000' => ['IDENTIFIER', 'ANALYSER', 'EVALUER', 'TRAITER', 'SURVEILLER'],
            'COSOERM' => ['GOUVERNANCE', 'STRATEGIE', 'PERFORMANCE', 'REVISION', 'REPORTING'],
            'ISO20000_ITIL' => ['SERVICE_STRATEGY', 'DESIGN', 'TRANSITION', 'OPERATION', 'IMPROVEMENT'],
            'ISO12207' => ['ACQUISITION', 'DEVELOPMENT', 'OPERATION', 'MAINTENANCE', 'QUALITY'],
            'ISO27000' => ['GOVERN', 'PROTECT', 'DETECT', 'RESPOND', 'IMPROVE'],
            'NIST_CSF' => ['IDENTIFY', 'PROTECT', 'DETECT', 'RESPOND', 'RECOVER'],
            'DGCPT' => ['gouvernance', 'missions', 'consolidation', 'reporting'],
        ];
    }
}
