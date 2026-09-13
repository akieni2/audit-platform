<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsEnterpriseGovernanceContext;
use Tests\TestCase;

class ExecutiveAnalyticsTest extends TestCase
{
    use BuildsEnterpriseGovernanceContext;
    use RefreshDatabase;

    public function test_executive_pages_render_enterprise_analytics_views(): void
    {
        $department = $this->governanceDepartment('EXEC');
        $user = $this->governanceUser($department, 'super_admin');
        $methodology = $this->governanceMethodology($department, ['is_global' => true]);
        $taxonomy = $this->governanceTaxonomy($department, ['is_national' => true]);
        $term = $this->governanceTaxonomyTerm($taxonomy);
        $library = $this->governanceControlLibrary($department, $methodology);
        $control = $this->governanceMethodologyControl($methodology, $this->governanceMethodologyCategory($methodology));
        $this->governanceControlMeasure($library, $control, $term, $department);

        $this->actingAs($user)
            ->get(route('executive.national-dashboard'))
            ->assertOk()
            ->assertSee('Tableau de bord national')
            ->assertSee('Plateforme d’analyse exécutive');

        $this->actingAs($user)
            ->get(route('executive.governance-overview'))
            ->assertOk()
            ->assertSee('Vue d’ensemble de la gouvernance')
            ->assertSee('Workflows globaux');
    }
}
