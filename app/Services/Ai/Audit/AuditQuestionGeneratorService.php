<?php

namespace App\Services\Ai\Audit;

use App\Domain\Ai\Enums\AiContextType;
use App\Domain\Ai\Enums\AiRecommendationType;
use App\Models\Mission;
use App\Models\User;
use App\Services\Ai\AiCopilotService;

class AuditQuestionGeneratorService
{
    public function __construct(private AiCopilotService $copilot) {}

    public function generate(Mission $mission, User $user, string $topic): array
    {
        return $this->copilot->assist(
            $mission,
            $user,
            AiContextType::Questionnaire,
            "Suggère des questions d'audit pour le thème : {$topic}. Format liste numérotée.",
            AiRecommendationType::AuditQuestion,
            ['topic' => $topic],
        );
    }
}
