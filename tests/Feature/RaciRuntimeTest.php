<?php

namespace Tests\Feature;

use App\Models\RaciAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsEnterpriseGovernanceContext;
use Tests\TestCase;

class RaciRuntimeTest extends TestCase
{
    use BuildsEnterpriseGovernanceContext;
    use RefreshDatabase;

    public function test_raci_runtime_can_assign_and_validate_responsibility(): void
    {
        $department = $this->governanceDepartment('RAR');
        $user = $this->governanceUser($department);
        $mission = $this->governanceMission($department, $user);
        $template = $this->governanceRaciTemplate($department);
        $role = $this->governanceRaciRole($template, $department);
        $assignee = User::factory()->create([
            'department_id' => $department->id,
            'role_id' => $this->governanceRole('agent_raci')->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('raci.assignments', $mission), [
                'raci_template_id' => $template->id,
                'raci_role_id' => $role->id,
                'assigned_user_id' => $assignee->id,
                'process_label' => 'Execution mission',
                'role_type' => 'responsible',
                'responsibility_level' => 'high',
            ])
            ->assertRedirect(route('raci.show', $mission));

        $assignment = RaciAssignment::query()->latest('id')->first();
        $this->assertNotNull($assignment);

        $this->actingAs($user)
            ->post(route('raci.validation', $mission), [
                'raci_assignment_id' => $assignment->id,
                'status' => 'approved',
            ])
            ->assertRedirect(route('raci.show', $mission));

        $this->assertDatabaseHas('raci_validations', [
            'raci_assignment_id' => $assignment->id,
            'status' => 'approved',
        ]);
    }
}
