<?php

namespace App\Services\Ai\Executive;

use App\Domain\Ai\Enums\AiContextType;
use App\Domain\Ai\Enums\AiRecommendationType;
use App\Models\Department;
use App\Models\Mission;
use App\Models\User;
use App\Services\Ai\AiCopilotService;

class ExecutiveNarrativeService
{
    public function __construct(private AiCopilotService $copilot) {}

    public function narrate(?Department $department, User $user): array
    {
        $mission = Mission::query()->visibleToUser($user)->when($department, fn ($q) => $q->where('department_id', $department->id))->latest('id')->firstOrFail();

        return $this->copilot->assist(
            $mission,
            $user,
            AiContextType::Executive,
            'Rédige une narration exécutive assistive des indicateurs (dashboard).',
            AiRecommendationType::ExecutiveInsight,
        );
    }
}
