<?php

namespace App\Services\Ai\Control;

use App\Domain\Ai\Enums\AiContextType;
use App\Domain\Ai\Enums\AiRecommendationType;
use App\Models\Mission;
use App\Models\User;
use App\Services\Ai\AiCopilotService;

class ControlGapDetectionService
{
    public function __construct(private AiCopilotService $copilot) {}

    public function detect(Mission $mission, User $user, string $framework): array
    {
        return $this->copilot->assist(
            $mission,
            $user,
            AiContextType::InternalControl,
            "Détecte les écarts de contrôle potentiels pour {$framework} (assistif).",
            AiRecommendationType::ComplianceGap,
            ['framework' => $framework],
        );
    }
}
