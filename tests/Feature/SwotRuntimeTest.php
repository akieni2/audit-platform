<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsEnterpriseGovernanceContext;
use Tests\TestCase;

class SwotRuntimeTest extends TestCase
{
    use BuildsEnterpriseGovernanceContext;
    use RefreshDatabase;

    public function test_swot_runtime_can_execute_analysis_for_a_mission(): void
    {
        $department = $this->governanceDepartment('SWR');
        $user = $this->governanceUser($department);
        $mission = $this->governanceMission($department, $user);
        $template = $this->governanceSwotTemplate($department);
        $category = $this->governanceSwotCategory($template);
        $this->governanceSwotEntry($template, $category, $department);

        $this->actingAs($user)
            ->post(route('swot.analyze', $mission), [
                'swot_template_id' => $template->id,
                'notes' => 'Analyse de mission',
            ])
            ->assertRedirect(route('swot.show', $mission));

        $this->assertDatabaseHas('swot_analyses', [
            'mission_id' => $mission->id,
            'swot_template_id' => $template->id,
        ]);

        $this->actingAs($user)
            ->get(route('swot.recommendations', $mission))
            ->assertOk()
            ->assertSee('Recommandations SWOT');
    }
}
