<?php

namespace App\Services\Ai\Audit;

use App\Domain\Ai\Enums\AiContextType;
use App\Domain\Ai\Enums\AiRecommendationType;
use App\Models\Mission;
use App\Models\User;
use App\Services\Ai\AiCopilotService;

class AuditProgramGeneratorService
{
    public function __construct(private AiCopilotService $copilot) {}

    public function generate(Mission $mission, User $user): array
    {
        return $this->copilot->assist(
            $mission,
            $user,
            AiContextType::Mission,
            'Propose un programme d\'audit assistif (phases, contrôles, livrables).',
            AiRecommendationType::AuditProgram,
        );
    }
}
