<?php

namespace Tests\Feature;

use App\Services\Taxonomy\TaxonomyEngineService;
use App\Services\Taxonomy\TaxonomyMappingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsEnterpriseGovernanceContext;
use Tests\TestCase;

class TaxonomyEngineTest extends TestCase
{
    use BuildsEnterpriseGovernanceContext;
    use RefreshDatabase;

    public function test_taxonomy_engine_resolves_aliases_and_model_mappings(): void
    {
        $department = $this->governanceDepartment('TAX');
        $user = $this->governanceUser($department, 'super_admin');
        $methodology = $this->governanceMethodology($department);
        $taxonomy = $this->governanceTaxonomy($department);
        $term = $this->governanceTaxonomyTerm($taxonomy, [
            'name' => 'Sécurité SI',
            'alias_terms' => ['cyber', 'sécurité si', 'it security'],
        ]);

        $engine = app(TaxonomyEngineService::class);
        $mapping = app(TaxonomyMappingService::class);

        $resolved = $engine->resolveAlias($taxonomy, 'cyber');
        $this->assertNotNull($resolved);
        $this->assertSame($term->id, $resolved?->id);

        $mapped = $mapping->mapModel($term, $methodology, 'methodology_reference', ['source' => 'test']);
        $this->assertSame($term->id, $mapped->taxonomy_term_id);
        $this->assertSame(1, $mapping->mappedTermsFor($methodology)->count());
        $this->assertCount(1, $engine->tree($taxonomy));

        $this->actingAs($user)
            ->get(route('enterprise.taxonomies'))
            ->assertOk()
            ->assertSee('Taxonomies enterprise')
            ->assertSee($taxonomy->name);
    }
}
