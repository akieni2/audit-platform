<?php

namespace App\Services\Ai\Knowledge;

class RegulationKnowledgeService
{
    /**
     * @return list<array{code: string, label: string}>
     */
    public function references(): array
    {
        return [
            ['code' => 'DGCPT-GOV', 'label' => 'Gouvernance institutionnelle DGCPT'],
            ['code' => 'ISO27001', 'label' => 'Sécurité de l\'information'],
            ['code' => 'RGPD', 'label' => 'Protection des données'],
        ];
    }
}
