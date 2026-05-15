<?php

namespace App\Services\Governance;

use App\Models\RaciMatrix;
use App\Models\User;
use App\Services\Raci\RaciAnalyticsService;

class EnterpriseRaciAnalyticsService
{
    public function __construct(
        private RaciAnalyticsService $analytics,
        private ResponsibilityConflictService $conflicts,
        private OrganizationalGapAnalysisService $gaps,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(?User $actor = null): array
    {
        $departmentId = $actor && ! $actor->canViewAllInstitutionalData() ? $actor->department_id : null;

        return [
            'snapshot' => $this->analytics->nationalSnapshot(),
            'conflicts' => $this->conflicts->snapshot($departmentId),
            'gaps' => $this->gaps->snapshot($departmentId),
            'heatmap' => RaciMatrix::query()
                ->when($departmentId !== null, fn ($query) => $query->where('department_id', $departmentId))
                ->get()
                ->groupBy('status')
                ->map(fn ($group, $status) => [
                    'status' => $status,
                    'count' => $group->count(),
                ])->values(),
        ];
    }
}
