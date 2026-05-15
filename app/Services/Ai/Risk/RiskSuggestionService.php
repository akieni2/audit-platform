<?php

namespace App\Services\Ai\Risk;

use App\Domain\Ai\Enums\AiContextType;
use App\Domain\Ai\Enums\AiRecommendationType;
use App\Models\Mission;
use App\Models\User;
use App\Services\Ai\AiCopilotService;

class RiskSuggestionService
{
    public function __construct(private AiCopilotService $copilot) {}

    public function suggest(Mission $mission, User $user): array
    {
        return $this->copilot->assist(
            $mission,
            $user,
            AiContextType::Risk,
            'Suggère des risques potentiels pour cette mission (assistif, validation humaine requise).',
            AiRecommendationType::RiskSuggestion,
        );
    }
}
