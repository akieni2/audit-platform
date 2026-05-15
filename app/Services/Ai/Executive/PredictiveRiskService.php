<?php

namespace App\Services\Ai\Executive;

use App\Domain\Ai\Enums\AiContextType;
use App\Domain\Ai\Enums\AiRecommendationType;
use App\Models\Department;
use App\Models\Mission;
use App\Models\User;
use App\Services\Ai\AiCopilotService;

class PredictiveRiskService
{
    public function __construct(private AiCopilotService $copilot) {}

    public function exposure(?Department $department, User $user): array
    {
        $mission = $this->resolveMission($department, $user);

        return $this->copilot->assist(
            $mission,
            $user,
            AiContextType::Executive,
            'Prédiction assistive d\'exposition aux risques (non contraignante).',
            AiRecommendationType::ExecutiveInsight,
            ['department_id' => $department?->id],
        );
    }

    private function resolveMission(?Department $department, User $user): Mission
    {
        $query = Mission::query()->visibleToUser($user)->latest('id');

        if ($department !== null) {
            $query->where('department_id', $department->id);
        }

        return $query->firstOr(function () use ($user) {
            return Mission::query()->visibleToUser($user)->firstOrFail();
        });
    }
}
