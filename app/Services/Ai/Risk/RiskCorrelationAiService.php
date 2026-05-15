<?php

namespace App\Services\Ai\Risk;

use App\Domain\Ai\Enums\AiContextType;
use App\Domain\Ai\Enums\AiRecommendationType;
use App\Models\Mission;
use App\Models\User;
use App\Services\Ai\AiCopilotService;

class RiskCorrelationAiService
{
    public function __construct(private AiCopilotService $copilot) {}

    public function correlate(Mission $mission, User $user, array $riskIds = []): array
    {
        return $this->copilot->assist(
            $mission,
            $user,
            AiContextType::Risk,
            'Analyse les corrélations entre risques identifiés (assistif).',
            AiRecommendationType::RiskSuggestion,
            ['risk_ids' => $riskIds],
        );
    }
}
