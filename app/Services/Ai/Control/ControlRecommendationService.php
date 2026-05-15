<?php

namespace App\Services\Ai\Control;

use App\Domain\Ai\Enums\AiContextType;
use App\Domain\Ai\Enums\AiRecommendationType;
use App\Models\Mission;
use App\Models\User;
use App\Services\Ai\AiCopilotService;

class ControlRecommendationService
{
    public function __construct(private AiCopilotService $copilot) {}

    public function recommend(Mission $mission, User $user, string $framework): array
    {
        return $this->copilot->assist(
            $mission,
            $user,
            AiContextType::InternalControl,
            "Recommande des contrôles manquants ou faibles pour {$framework}.",
            AiRecommendationType::ControlSuggestion,
            ['framework' => $framework],
        );
    }
}
