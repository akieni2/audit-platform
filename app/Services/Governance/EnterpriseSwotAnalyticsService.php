<?php

namespace App\Services\Governance;

use App\Models\SwotAnalysis;
use App\Models\User;
use App\Services\Swot\SwotAnalyticsService;
use App\Services\Swot\SwotConsolidationService;

class EnterpriseSwotAnalyticsService
{
    public function __construct(
        private SwotAnalyticsService $analytics,
        private SwotConsolidationService $consolidation,
        private StrategicRiskAlignmentService $alignment,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(?User $actor = null): array
    {
        $departmentId = $actor && ! $actor->canViewAllInstitutionalData() ? $actor->department_id : null;

        return [
            'snapshot' => $this->analytics->nationalSnapshot(),
            'consolidation' => $this->consolidation->snapshot($departmentId),
            'alignment' => $this->alignment->snapshot($departmentId),
            'trends' => SwotAnalysis::query()
                ->when($departmentId !== null, fn ($query) => $query->where('department_id', $departmentId))
                ->get()
                ->groupBy(fn (SwotAnalysis $analysis) => optional($analysis->created_at)->format('Y-m') ?: 'n/a')
                ->map(fn ($group, $month) => [
                    'month' => $month,
                    'count' => $group->count(),
                    'average_score' => round($group->avg('weighted_score') ?: 0, 2),
                ])->values(),
        ];
    }
}
