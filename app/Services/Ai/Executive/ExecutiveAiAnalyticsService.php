<?php

namespace App\Services\Ai\Executive;

use App\Models\Department;
use App\Models\User;

class ExecutiveAiAnalyticsService
{
    public function __construct(
        private PredictiveRiskService $predictive,
        private ExecutiveNarrativeService $narrative,
        private StrategicInsightService $strategic,
    ) {}

    public function dashboardInsights(?Department $department, User $user): array
    {
        return [
            'predictive' => $this->predictive->exposure($department, $user),
            'narrative' => $this->narrative->narrate($department, $user),
            'strategic' => $this->strategic->insights($department, $user),
        ];
    }
}
