<?php

namespace App\Services\Reporting;

use App\Models\Department;
use App\Models\Mission;
use App\Models\User;
use App\Services\Governance\DepartmentConsolidationService;
use App\Services\Intelligence\EnterpriseRiskIntelligenceService;
use App\Services\Methodologies\MethodologyWorkflowMappingService;

class DynamicReportBuilder
{
    public function __construct(
        private EnterpriseRiskIntelligenceService $intelligence,
        private DepartmentConsolidationService $consolidation,
        private MethodologyWorkflowMappingService $methodologyMappings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function missionPayload(Mission $mission): array
    {
        $mission->loadMissing(['department', 'workflowInstance.workflowTemplate.methodologyTemplate']);
        $methodology = $mission->workflowInstance?->workflowTemplate?->methodologyTemplate;

        return [
            'scope' => 'mission',
            'mission' => $mission,
            'intelligence' => $this->intelligence->snapshot(['mission_id' => $mission->id]),
            'methodology' => $methodology,
            'mapping_stack' => $methodology ? $this->methodologyMappings->resolveStack($methodology, $mission->department_id) : null,
            'formats' => ['pdf', 'word', 'excel'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function departmentPayload(Department $department): array
    {
        return [
            'scope' => 'department',
            'department' => $department,
            'intelligence' => $this->intelligence->snapshot(['department_id' => $department->id]),
            'consolidation' => $this->consolidation->snapshot($department->id),
            'formats' => ['pdf', 'word', 'excel'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function nationalPayload(?User $actor = null): array
    {
        $filters = [];
        if ($actor instanceof User && method_exists($actor, 'canViewAllInstitutionalData') && ! $actor->canViewAllInstitutionalData() && $actor->department_id !== null) {
            $filters['department_id'] = $actor->department_id;
        }

        return [
            'scope' => 'national',
            'intelligence' => $this->intelligence->snapshot($filters),
            'consolidation' => $this->consolidation->snapshot($filters['department_id'] ?? null),
            'formats' => ['pdf', 'word', 'excel'],
        ];
    }
}
