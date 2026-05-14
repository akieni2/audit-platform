<?php

namespace App\Services\Intelligence;

use App\Services\Risk\RiskRegistryQueryService;
use Illuminate\Support\Str;

class RiskCorrelationService
{
    public function __construct(
        private RiskRegistryQueryService $registry,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function correlate(array $filters = [], int $limit = 10): array
    {
        $official = $this->registry->registry($filters);
        $intake = $this->registry->intake($filters);
        $clusters = [];

        foreach ($official->concat($intake) as $risk) {
            $label = $this->correlationKey(
                (string) ($risk->category ?? $risk->criticality ?? $risk->title ?? $risk->description ?? 'generic')
            );

            $clusters[$label] ??= [
                'cluster' => Str::headline(str_replace('-', ' ', $label)),
                'count' => 0,
                'departments' => [],
                'criticality' => [],
            ];

            $clusters[$label]['count']++;
            $dept = $risk->ownerDepartment?->code
                ?? $risk->sourceDepartment?->code
                ?? $risk->mission?->department?->code
                ?? 'NATIONAL';
            $criticality = (string) ($risk->criticality ?? 'medium');

            $clusters[$label]['departments'][$dept] = true;
            $clusters[$label]['criticality'][$criticality] = ($clusters[$label]['criticality'][$criticality] ?? 0) + 1;
        }

        return collect($clusters)
            ->map(fn (array $cluster) => [
                ...$cluster,
                'departments' => array_values(array_keys($cluster['departments'])),
            ])
            ->sortByDesc('count')
            ->take($limit)
            ->values()
            ->all();
    }

    private function correlationKey(string $value): string
    {
        $normalized = Str::of(Str::lower(trim($value)))
            ->ascii()
            ->replaceMatches('/[^a-z0-9 ]+/', ' ')
            ->squish()
            ->value();

        $tokens = collect(explode(' ', $normalized))
            ->filter(fn ($token) => strlen($token) >= 4)
            ->take(3)
            ->all();

        return $tokens !== [] ? implode('-', $tokens) : 'generic-risk';
    }
}
