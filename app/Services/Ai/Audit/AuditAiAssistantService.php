<?php

namespace App\Services\Ai\Audit;

use App\Domain\Ai\Enums\AiContextType;
use App\Domain\Ai\Enums\AiRecommendationType;
use App\Models\Mission;
use App\Models\User;
use App\Services\Ai\AiCopilotService;

class AuditAiAssistantService
{
    public function __construct(
        private AiCopilotService $copilot,
        private AuditAnalysisService $analysis,
    ) {}

    public function missionSummary(Mission $mission, User $user): array
    {
        return $this->copilot->assist(
            $mission,
            $user,
            AiContextType::Mission,
            'Produis une synthèse assistive de la mission d\'audit (points forts, zones faibles, prochaines étapes).',
            AiRecommendationType::General,
        );
    }

    public function analyzeInconsistencies(Mission $mission, User $user, array $responses = []): array
    {
        return $this->analysis->detectInconsistencies($mission, $user, $responses);
    }
}
