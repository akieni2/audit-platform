<?php

namespace App\Services\Reporting;

use App\Models\Department;
use App\Models\User;
use App\Services\Governance\ExecutiveAnalyticsService;

class ExecutiveReportService
{
    public function __construct(
        private DynamicReportBuilder $reports,
        private ExecutiveAnalyticsService $analytics,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function nationalReport(?User $actor = null): array
    {
        return [
            'payload' => $this->reports->nationalPayload($actor),
            'analytics' => $this->analytics->nationalSnapshot($actor),
            'title' => 'Rapport exécutif national',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function departmentReport(Department $department, ?User $actor = null): array
    {
        return [
            'payload' => $this->reports->departmentPayload($department),
            'analytics' => $this->analytics->departmentComparison($actor),
            'title' => 'Rapport exécutif départemental',
        ];
    }
}
