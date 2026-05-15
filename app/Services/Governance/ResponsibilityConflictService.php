<?php

namespace App\Services\Governance;

use App\Models\RaciAssignment;

class ResponsibilityConflictService
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(?int $departmentId = null): array
    {
        $assignments = RaciAssignment::query()
            ->when($departmentId !== null, fn ($query) => $query->where('department_id', $departmentId))
            ->get();

        $conflicts = $assignments
            ->groupBy(fn (RaciAssignment $assignment) => ($assignment->mission_id ?? 0).':'.($assignment->process_label ?? 'n/a'))
            ->map(function ($group, $key) {
                $accountables = $group->where('role_type', 'accountable');

                return [
                    'key' => $key,
                    'accountables' => $accountables->count(),
                    'assignments' => $group->count(),
                ];
            })
            ->filter(fn (array $row) => $row['accountables'] > 1)
            ->values();

        return [
            'conflicts' => $conflicts,
            'total_conflicts' => $conflicts->count(),
            'overloaded_users' => $assignments
                ->groupBy('assigned_user_id')
                ->filter(fn ($group) => $group->count() > 3)
                ->count(),
        ];
    }
}
