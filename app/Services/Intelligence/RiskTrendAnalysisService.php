<?php

namespace App\Services\Intelligence;

use App\Services\Risk\RiskRegistryQueryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RiskTrendAnalysisService
{
    public function __construct(
        private RiskRegistryQueryService $registry,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function monthly(array $filters = [], int $monthsBack = 12): array
    {
        return $this->registry->trends($filters, $monthsBack);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function recurring(array $filters = [], int $limit = 8): array
    {
        $official = $this->registry->registry($filters);
        $intake = $this->registry->intake($filters);
        $groups = [];

        foreach ($official->concat($intake) as $risk) {
            $label = $this->normalizeLabel(
                (string) ($risk->category ?? $risk->criticality ?? $risk->title ?? $risk->description ?? 'Risque non qualifié')
            );
            if ($label === '') {
                $label = 'risque-non-qualifie';
            }

            $groups[$label] ??= [
                'label' => Str::headline(str_replace('-', ' ', $label)),
                'count' => 0,
                'departments' => [],
            ];

            $groups[$label]['count']++;
            $dept = $risk->ownerDepartment?->code
                ?? $risk->sourceDepartment?->code
                ?? $risk->mission?->department?->code
                ?? 'NATIONAL';
            $groups[$label]['departments'][$dept] = true;
        }

        return collect($groups)
            ->map(fn (array $entry) => [
                ...$entry,
                'departments' => array_values(array_keys($entry['departments'])),
            ])
            ->sortByDesc('count')
            ->take($limit)
            ->values()
            ->all();
    }

    private function normalizeLabel(string $value): string
    {
        return Str::of(Str::lower(trim($value)))
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-')
            ->value();
    }
}
