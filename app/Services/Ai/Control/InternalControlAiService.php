<?php

namespace App\Services\Ai\Control;

use App\Models\Mission;
use App\Models\User;

class InternalControlAiService
{
    public function __construct(
        private ComplianceAnalysisService $compliance,
        private ControlGapDetectionService $gaps,
        private ControlRecommendationService $recommendations,
    ) {}

    public function analyze(Mission $mission, User $user, string $framework = 'ISO27001'): array
    {
        return [
            'compliance' => $this->compliance->analyze($mission, $user, $framework),
            'gaps' => $this->gaps->detect($mission, $user, $framework),
            'recommendations' => $this->recommendations->recommend($mission, $user, $framework),
        ];
    }
}
