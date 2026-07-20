<?php

namespace App\Services\Governance;

use App\Models\ControlLibrary;
use App\Models\Department;
use App\Models\MethodologyTemplate;
use App\Models\Taxonomy;
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
            'harmonized_taxonomy' => $this->harmonizedTaxonomySnapshot($departmentId),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function harmonizedTaxonomySnapshot(?int $departmentId = null): array
    {
        $taxonomy = Taxonomy::query()
            ->where('slug', 'dgcpt-risk-taxonomy')
            ->with('terms.methodologyMappings.methodologyTemplate')
            ->first();

        if ($taxonomy === null) {
            return [];
        }

        $risks = $this->registry->registry($departmentId !== null ? ['department_id' => $departmentId] : []);
        $intake = $this->registry->intake($departmentId !== null ? ['department_id' => $departmentId] : []);

        return $taxonomy->terms->map(function ($term) use ($risks, $intake) {
            $aliases = collect($term->alias_terms ?? [])
                ->push($term->code)
                ->push($term->name)
                ->map(fn ($value) => mb_strtolower((string) $value))
                ->filter()
                ->values();

            $matchesOfficial = $risks->filter(function ($risk) use ($aliases): bool {
                $haystack = mb_strtolower(implode(' ', array_filter([
                    $risk->description,
                    $risk->departement,
                    $risk->niveau,
                    $risk->criticality,
                    $risk->metadata['taxonomy_code'] ?? null,
                    $risk->metadata['risk_category'] ?? null,
                ])));

                return $aliases->contains(fn (string $alias) => $alias !== '' && str_contains($haystack, $alias));
            });

            $matchesIntake = $intake->filter(function ($risk) use ($aliases): bool {
                $haystack = mb_strtolower(implode(' ', array_filter([
                    $risk->title ?? null,
                    $risk->description ?? null,
                    $risk->category ?? null,
                    $risk->metadata['taxonomy_code'] ?? null,
                    $risk->metadata['risk_category'] ?? null,
                ])));

                return $aliases->contains(fn (string $alias) => $alias !== '' && str_contains($haystack, $alias));
            });

            return [
                'term' => $term,
                'official_count' => $matchesOfficial->count(),
                'intake_count' => $matchesIntake->count(),
                'residual_exposure' => (int) $matchesOfficial->sum(fn ($risk) => (int) ($risk->residual_score ?? $risk->score_residuel ?? 0)),
                'mapped_methodologies' => $term->methodologyMappings
                    ->pluck('methodologyTemplate')
                    ->filter()
                    ->unique('id')
                    ->count(),
            ];
        })->values()->all();
    }
}
