<?php

namespace App\Services\Swot;

use App\Models\Department;
use App\Models\SwotAnalysis;
use App\Models\SwotRecommendation;

class SwotConsolidationService
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(?int $departmentId = null): array
    {
        $rows = Department::query()
            ->where('active', true)
            ->when($departmentId !== null, fn ($query) => $query->whereKey($departmentId))
            ->orderBy('code')
            ->get()
            ->map(function (Department $department) {
                $analyses = SwotAnalysis::query()->where('department_id', $department->id)->get();

                return [
                    'department' => $department,
                    'analyses_count' => $analyses->count(),
                    'average_score' => round($analyses->avg('weighted_score') ?: 0, 2),
                    'recommendations_count' => SwotRecommendation::query()->where('department_id', $department->id)->count(),
                ];
            })
            ->values();

        return [
            'rows' => $rows,
            'totals' => [
                'departments' => $rows->count(),
                'analyses' => $rows->sum('analyses_count'),
                'recommendations' => $rows->sum('recommendations_count'),
                'average_score' => round($rows->avg('average_score') ?: 0, 2),
            ],
        ];
    }
}
