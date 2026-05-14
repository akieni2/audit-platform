<?php

namespace App\Services\Intelligence;

use App\Services\Risk\RiskRegistryQueryService;

class CrossDepartmentRiskAggregator
{
    public function __construct(
        private RiskRegistryQueryService $registry,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function aggregate(array $filters = []): array
    {
        $official = $this->registry->registry($filters);
        $intake = $this->registry->intake($filters);
        $departments = [];

        foreach ($official as $risk) {
            $code = $risk->ownerDepartment?->code ?? $risk->sourceDepartment?->code ?? 'NATIONAL';
            $departments[$code] ??= [
                'department' => $code,
                'registry_count' => 0,
                'intake_count' => 0,
                'critical_open' => 0,
                'residual_exposure' => 0,
                'cross_department' => 0,
            ];

            $departments[$code]['registry_count']++;
            $departments[$code]['residual_exposure'] += (int) ($risk->residual_score ?? $risk->score_residuel ?? 0);
            $departments[$code]['cross_department'] += $risk->cross_department ? 1 : 0;
            $departments[$code]['critical_open'] += (string) ($risk->criticality ?? $risk->criticite_residuel ?? '') === 'critical' ? 1 : 0;
        }

        foreach ($intake as $risk) {
            $code = $risk->ownerDepartment?->code ?? $risk->mission?->department?->code ?? 'NATIONAL';
            $departments[$code] ??= [
                'department' => $code,
                'registry_count' => 0,
                'intake_count' => 0,
                'critical_open' => 0,
                'residual_exposure' => 0,
                'cross_department' => 0,
            ];

            $departments[$code]['intake_count']++;
            $departments[$code]['critical_open'] += (string) ($risk->criticality ?? '') === 'critical' ? 1 : 0;
        }

        return collect($departments)
            ->sortByDesc(fn (array $entry) => $entry['critical_open'] + $entry['registry_count'] + $entry['intake_count'])
            ->values()
            ->all();
    }
}
