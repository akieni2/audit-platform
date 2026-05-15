<?php

namespace App\Services\Ai\Audit;

use App\Domain\Ai\Enums\AiContextType;
use App\Domain\Ai\Enums\AiRecommendationType;
use App\Models\Mission;
use App\Models\User;
use App\Services\Ai\AiCopilotService;

class AuditAnalysisService
{
    public function __construct(private AiCopilotService $copilot) {}

    public function detectInconsistencies(Mission $mission, User $user, array $responses): array
    {
        return $this->copilot->assist(
            $mission,
            $user,
            AiContextType::Entretien,
            'Analyse les réponses d\'entretien et signale les incohérences potentielles (assistif uniquement).',
            AiRecommendationType::RuntimeHint,
            ['responses' => $responses],
        );
    }

    public function weakAreas(Mission $mission, User $user): array
    {
        return $this->copilot->assist(
            $mission,
            $user,
            AiContextType::Mission,
            'Identifie les zones faibles potentielles de la mission (assistif).',
            AiRecommendationType::General,
        );
    }
}
