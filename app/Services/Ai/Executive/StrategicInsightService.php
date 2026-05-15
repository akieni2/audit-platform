<?php

namespace App\Services\Ai\Executive;

use App\Domain\Ai\Enums\AiContextType;
use App\Domain\Ai\Enums\AiRecommendationType;
use App\Models\Department;
use App\Models\Mission;
use App\Models\User;
use App\Services\Ai\AiCopilotService;

class StrategicInsightService
{
    public function __construct(private AiCopilotService $copilot) {}

    public function insights(?Department $department, User $user): array
    {
        $mission = Mission::query()->visibleToUser($user)->when($department, fn ($q) => $q->where('department_id', $department->id))->latest('id')->firstOrFail();

        return $this->copilot->assist(
            $mission,
            $user,
            AiContextType::Executive,
            'Produis des insights stratégiques assistifs (départements critiques, risques émergents).',
            AiRecommendationType::StrategicInsight,
        );
    }
}
