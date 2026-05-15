<?php

namespace Tests\Feature;

use App\Services\Raci\RaciAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsEnterpriseGovernanceContext;
use Tests\TestCase;

class RaciAnalyticsTest extends TestCase
{
    use BuildsEnterpriseGovernanceContext;
    use RefreshDatabase;

    public function test_executive_raci_dashboard_renders_conflicts_and_gaps(): void
    {
        $department = $this->governanceDepartment('RAX');
        $user = $this->governanceUser($department, 'inspecteur_services');
        $mission = $this->governanceMission($department, $user);
        $template = $this->governanceRaciTemplate($department, ['is_global' => true]);
        $role = $this->governanceRaciRole($template, $department, ['role_type' => 'accountable']);

        app(RaciAssignmentService::class)->assignForMission($template, $mission, [
            'status' => 'assigned',
            'process_label' => 'Cloture',
            'assignments' => [[
                'raci_role_id' => $role->id,
                'process_label' => 'Cloture',
                'role_type' => 'accountable',
                'responsibility_level' => 'high',
                'status' => 'assigned',
            ]],
        ], $user);

        $this->actingAs($user)
            ->get(route('executive.raci-dashboard'))
            ->assertOk()
            ->assertSee('RACI Dashboard')
            ->assertSee('Gaps');
    }
}
