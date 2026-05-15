<?php

namespace App\Services\Governance;

use App\Models\Risque;
use App\Models\SwotRecommendation;
use Illuminate\Support\Facades\Schema;

class StrategicRiskAlignmentService
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(?int $departmentId = null): array
    {
        $recommendations = SwotRecommendation::query()
            ->when($departmentId !== null, fn ($query) => $query->where('department_id', $departmentId))
            ->get();

        $riskCount = 0;
        if (Schema::hasTable('risques')) {
            $riskCount = Risque::query()->count();
        }

        return [
            'recommendations' => $recommendations->count(),
            'critical_recommendations' => $recommendations->where('priority_level', 'critical')->count(),
            'risk_registry_count' => $riskCount,
            'alignment_index' => $riskCount > 0
                ? round(($recommendations->count() / $riskCount) * 100, 2)
                : 0.0,
        ];
    }
}
