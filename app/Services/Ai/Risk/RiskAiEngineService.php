<?php

namespace App\Services\Ai\Risk;

use App\Models\Mission;
use App\Models\User;

class RiskAiEngineService
{
    public function __construct(
        private RiskSuggestionService $suggestions,
        private RiskScoringAiService $scoring,
        private RiskTrendAiService $trends,
    ) {}

    public function analyzeMission(Mission $mission, User $user): array
    {
        return [
            'suggestions' => $this->suggestions->suggest($mission, $user),
            'scoring' => $this->scoring->score($mission, $user),
            'trends' => $this->trends->trends($mission, $user),
        ];
    }
}
