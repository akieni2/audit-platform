<?php

namespace App\Services\Ai\Risk;

use App\Domain\Ai\Enums\AiContextType;
use App\Domain\Ai\Enums\AiRecommendationType;
use App\Models\Mission;
use App\Models\User;
use App\Services\Ai\AiCopilotService;

class RiskTrendAiService
{
    public function __construct(private AiCopilotService $copilot) {}

    public function trends(Mission $mission, User $user): array
    {
        return $this->copilot->assist(
            $mission,
            $user,
            AiContextType::Risk,
            'Décris les tendances de risques observables (assistif).',
            AiRecommendationType::RiskMitigation,
        );
    }
}
