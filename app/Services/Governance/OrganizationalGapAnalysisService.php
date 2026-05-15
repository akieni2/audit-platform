<?php

namespace App\Services\Governance;

use App\Models\RaciAssignment;

class OrganizationalGapAnalysisService
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(?int $departmentId = null): array
    {
        $assignments = RaciAssignment::query()
            ->when($departmentId !== null, fn ($query) => $query->where('department_id', $departmentId))
            ->get();

        $byProcess = $assignments->groupBy('process_label');

        $gaps = $byProcess->map(function ($group, $processLabel) {
            return [
                'process_label' => $processLabel,
                'missing_accountable' => $group->where('role_type', 'accountable')->count() === 0,
                'missing_responsible' => $group->where('role_type', 'responsible')->count() === 0,
                'participants' => $group->pluck('assigned_user_id')->filter()->unique()->count(),
            ];
        })->values();

        return [
            'gaps' => $gaps,
            'totals' => [
                'missing_accountable' => $gaps->where('missing_accountable', true)->count(),
                'missing_responsible' => $gaps->where('missing_responsible', true)->count(),
                'processes' => $gaps->count(),
            ],
        ];
    }
}
