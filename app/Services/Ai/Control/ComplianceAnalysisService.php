<?php

namespace App\Services\Ai\Control;

use App\Domain\Ai\Enums\AiContextType;
use App\Domain\Ai\Enums\AiRecommendationType;
use App\Models\Mission;
use App\Models\User;
use App\Services\Ai\AiCopilotService;

class ComplianceAnalysisService
{
    public function __construct(private AiCopilotService $copilot) {}

    public function analyze(Mission $mission, User $user, string $framework): array
    {
        return $this->copilot->assist(
            $mission,
            $user,
            AiContextType::InternalControl,
            "Analyse la conformité assistive au référentiel {$framework}.",
            AiRecommendationType::ComplianceGap,
            ['framework' => $framework],
        );
    }
}
