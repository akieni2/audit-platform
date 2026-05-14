<?php

namespace App\Services\Governance;

use App\Models\ControlLibrary;
use App\Models\Department;
use App\Models\MethodologyTemplate;
use App\Models\WorkflowTemplate;
use App\Services\Risk\RiskRegistryQueryService;

class DepartmentConsolidationService
{
    public function __construct(
        private RiskRegistryQueryService $registry,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function snapshot(?int $departmentId = null): array
    {
        $departments = Department::query()
            ->where('active', true)
            ->when($departmentId !== null, fn ($query) => $query->whereKey($departmentId))
            ->orderBy('code')
            ->get();

        $rows = $departments->map(function (Department $department) {
            $filters = ['department_id' => $department->id];
            $kpis = $this->registry->kpis($filters);

            return [
                'department' => $department,
                'kpis' => $kpis,
                'workflow_templates' => WorkflowTemplate::query()->where(fn ($query) => $query->whereNull('department_id')->orWhere('department_id', $department->id))->count(),
                'methodologies' => MethodologyTemplate::query()->where(fn ($query) => $query->whereNull('department_id')->orWhere('department_id', $department->id))->count(),
                'control_libraries' => ControlLibrary::query()->where(fn ($query) => $query->whereNull('department_id')->orWhere('department_id', $department->id))->count(),
            ];
        })->values();

        return [
            'rows' => $rows,
            'totals' => [
                'departments' => $rows->count(),
                'critical_open' => $rows->sum(fn (array $row) => (int) ($row['kpis']['critical_open'] ?? 0)),
                'registry' => $rows->sum(fn (array $row) => (int) ($row['kpis']['total_registry'] ?? 0)),
                'intake' => $rows->sum(fn (array $row) => (int) ($row['kpis']['total_intake'] ?? 0)),
            ],
        ];
    }
}
