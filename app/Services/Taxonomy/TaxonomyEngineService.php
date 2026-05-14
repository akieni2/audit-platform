<?php

namespace App\Services\Taxonomy;

use App\Models\Taxonomy;
use App\Models\TaxonomyTerm;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TaxonomyEngineService
{
    /**
     * @return Collection<int, Taxonomy>
     */
    public function catalog(?string $taxonomyType = null, ?int $departmentId = null): Collection
    {
        return Taxonomy::query()
            ->with('terms')
            ->when($taxonomyType !== null, fn ($query) => $query->where('taxonomy_type', $taxonomyType))
            ->when($departmentId !== null, function ($query) use ($departmentId) {
                $query->where(function ($inner) use ($departmentId) {
                    $inner->where('is_national', true)
                        ->orWhereNull('department_id')
                        ->orWhere('department_id', $departmentId);
                });
            })
            ->orderByDesc('is_national')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function tree(Taxonomy $taxonomy): array
    {
        $taxonomy->loadMissing('terms.children');

        $roots = $taxonomy->terms->whereNull('parent_id')->sortBy('sort_order')->values();

        return $roots->map(fn (TaxonomyTerm $term) => $this->termNode($term))->all();
    }

    public function resolveAlias(Taxonomy $taxonomy, string $value): ?TaxonomyTerm
    {
        $needle = Str::lower(trim($value));
        $taxonomy->loadMissing('terms');

        return $taxonomy->terms->first(function (TaxonomyTerm $term) use ($needle) {
            $candidates = collect([$term->name, $term->code])
                ->merge($term->alias_terms ?? [])
                ->filter()
                ->map(fn ($item) => Str::lower(trim((string) $item)));

            return $candidates->contains($needle);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function termNode(TaxonomyTerm $term): array
    {
        $term->loadMissing('children');

        return [
            'id' => $term->id,
            'name' => $term->name,
            'code' => $term->code,
            'aliases' => $term->alias_terms ?? [],
            'children' => $term->children
                ->sortBy('sort_order')
                ->values()
                ->map(fn (TaxonomyTerm $child) => $this->termNode($child))
                ->all(),
        ];
    }
}
