<?php

namespace App\Services\Raci;

use App\Models\Department;
use App\Models\Mission;
use App\Models\RaciAssignment;
use App\Models\RaciMatrix;
use App\Models\RaciTemplate;

class RaciAnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function missionSnapshot(Mission $mission, ?RaciTemplate $template = null): array
    {
        $matrix = RaciMatrix::query()
            ->where('mission_id', $mission->id)
            ->with(['assignments.raciRole', 'validations'])
            ->latest('id')
            ->first();

        $template ??= $matrix?->raciTemplate;

        return $this->composeSnapshot('mission', $mission->organisation, $template, $matrix);
    }

    /**
     * @return array<string, mixed>
     */
    public function departmentSnapshot(Department $department): array
    {
        $matrix = RaciMatrix::query()
            ->where('department_id', $department->id)
            ->with(['assignments.raciRole', 'validations'])
            ->latest('id')
            ->first();

        return $this->composeSnapshot('department', $department->name, $matrix?->raciTemplate, $matrix);
    }

    /**
     * @return array<string, mixed>
     */
    public function nationalSnapshot(): array
    {
        $matrix = RaciMatrix::query()
            ->with(['assignments.raciRole', 'validations'])
            ->latest('id')
            ->first();

        return $this->composeSnapshot('national', 'National', $matrix?->raciTemplate, $matrix);
    }

    /**
     * @return array<string, mixed>
     */
    private function composeSnapshot(string $scope, string $label, ?RaciTemplate $template, ?RaciMatrix $matrix): array
    {
        $assignments = $matrix?->assignments ?? collect();
        $validations = $matrix?->validations ?? collect();

        return [
            'scope' => $scope,
            'label' => $label,
            'template' => $template,
            'matrix' => $matrix,
            'assignments' => $assignments,
            'validations' => $validations,
            'kpis' => [
                'assignments' => $assignments->count(),
                'validated' => $validations->where('status', 'approved')->count(),
                'responsible' => $assignments->where('role_type', 'responsible')->count(),
                'accountable' => $assignments->where('role_type', 'accountable')->count(),
            ],
            'overload' => $assignments
                ->groupBy('assigned_user_id')
                ->map(fn ($group, $userId) => [
                    'assigned_user_id' => $userId,
                    'count' => $group->count(),
                    'critical' => $group->where('responsibility_level', 'critical')->count(),
                ])
                ->values(),
            'processes' => $assignments
                ->groupBy('process_label')
                ->map(fn ($group, $processLabel) => [
                    'process_label' => $processLabel,
                    'count' => $group->count(),
                ])->values(),
        ];
    }
}
