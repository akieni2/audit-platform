<?php

namespace App\Services\Ai\Knowledge;

class MethodologyKnowledgeService
{
    /**
     * @return array<string, list<string>>
     */
    public function frameworks(): array
    {
        return [
            'ISO27001' => ['A.5', 'A.6', 'A.8', 'A.12'],
            'COSO' => ['environment', 'risk_assessment', 'control_activities', 'monitoring'],
            'COBIT' => ['EDM', 'APO', 'BAI', 'DSS', 'MEA'],
            'ITIL' => ['incident', 'change', 'problem', 'service_level'],
            'DGCPT' => ['gouvernance', 'missions', 'consolidation', 'reporting'],
        ];
    }

    public function controlsFor(string $framework): array
    {
        return $this->frameworks()[$framework] ?? [];
    }
}
