<?php

namespace App\Services\Ai\Risk;

use App\Domain\Ai\Enums\AiContextType;
use App\Domain\Ai\Enums\AiRecommendationType;
use App\Models\Mission;
use App\Models\User;
use App\Services\Ai\AiCopilotService;

class RiskScoringAiService
{
    public function __construct(private AiCopilotService $copilot) {}

    public function score(Mission $mission, User $user): array
    {
        return $this->copilot->assist(
            $mission,
            $user,
            AiContextType::Risk,
            'Propose un scoring intelligent assistif des risques (sans décision automatique).',
            AiRecommendationType::RiskSuggestion,
        );
    }
}
