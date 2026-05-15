<?php

namespace App\Services\Ai\Governance;

use App\Models\AiRecommendation;

class AiExplainabilityService
{
    public function explain(AiRecommendation $recommendation): array
    {
        return [
            'recommendation_id' => $recommendation->id,
            'type' => $recommendation->recommendation_type,
            'confidence' => $recommendation->confidence_level,
            'rationale' => $recommendation->rationale,
            'provenance' => $recommendation->payload['provenance'] ?? [],
            'human_validation_required' => true,
            'source_of_truth' => 'human_auditor',
        ];
    }
}
