<?php

namespace App\Services\Runtime;

use App\Services\Risk\RiskScoringEngine;

class IntelligentScoringService
{
    public function __construct(
        private RiskScoringEngine $scoring,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function scoreRiskPayload(array $payload): array
    {
        $inherent = $this->scoring->inherent(
            $payload['probability'] ?? null,
            $payload['impact'] ?? null,
            $payload['criticality'] ?? null,
        );

        $residual = $this->scoring->residual(
            $payload['probability'] ?? null,
            $payload['impact'] ?? null,
            (int) ($payload['controls'] ?? 0),
            $payload['mitigation'] ?? null,
            $payload['criticality'] ?? null,
        );

        $confidence = max(40, min(95, 50 + ((int) $inherent['score']) - ((int) $residual['score'] / 2)));

        return [
            'inherent' => $inherent,
            'residual' => $residual,
            'confidence' => (int) round($confidence),
            'suggested_criticality' => $residual['criticality'],
        ];
    }
}
