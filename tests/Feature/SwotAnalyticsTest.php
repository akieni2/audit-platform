<?php

namespace Tests\Feature;

use App\Services\Swot\SwotAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsEnterpriseGovernanceContext;
use Tests\TestCase;

class SwotAnalyticsTest extends TestCase
{
    use BuildsEnterpriseGovernanceContext;
    use RefreshDatabase;

    public function test_executive_swot_dashboard_renders_enterprise_snapshot(): void
    {
        $department = $this->governanceDepartment('SWA');
        $user = $this->governanceUser($department, 'inspecteur_services');
        $mission = $this->governanceMission($department, $user);
        $template = $this->governanceSwotTemplate($department, ['is_global' => true]);
        $category = $this->governanceSwotCategory($template);
        $this->governanceSwotEntry($template, $category, $department);

        app(SwotAnalyticsService::class)->runMissionAnalysis($template, $mission, ['actor_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('executive.swot-dashboard'))
            ->assertOk()
            ->assertSee('SWOT Dashboard')
            ->assertSee('Consolidation');
    }
}
