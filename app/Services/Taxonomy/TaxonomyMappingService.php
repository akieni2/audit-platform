<?php

namespace App\Services\Taxonomy;

use App\Models\TaxonomyMapping;
use App\Models\TaxonomyTerm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class TaxonomyMappingService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function mapModel(TaxonomyTerm $term, Model $model, string $mappingType = 'direct', array $metadata = []): TaxonomyMapping
    {
        return TaxonomyMapping::query()->updateOrCreate(
            [
                'taxonomy_term_id' => $term->id,
                'mappable_type' => $model::class,
                'mappable_id' => $model->getKey(),
                'mapping_type' => $mappingType,
            ],
            [
                'taxonomy_id' => $term->taxonomy_id,
                'department_id' => data_get($model, 'department_id'),
                'metadata' => $metadata,
            ]
        );
    }

    /**
     * @return Collection<int, TaxonomyMapping>
     */
    public function mappedTermsFor(Model $model): Collection
    {
        return TaxonomyMapping::query()
            ->with(['taxonomy', 'taxonomyTerm'])
            ->where('mappable_type', $model::class)
            ->where('mappable_id', $model->getKey())
            ->latest('id')
            ->get();
    }
}
