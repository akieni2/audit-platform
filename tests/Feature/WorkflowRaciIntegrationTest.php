<?php

namespace Tests\Feature;

use App\Models\WorkflowTransition;
use App\Services\Workflow\WorkflowExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsWorkflowRuntimeContext;
use Tests\TestCase;

class WorkflowRaciIntegrationTest extends TestCase
{
    use BuildsWorkflowRuntimeContext;
    use RefreshDatabase;

    public function test_workflow_raci_stage_can_complete_and_advance_runtime(): void
    {
        $department = $this->createDepartment('WRI');
        $user = $this->createUser('inspecteur_services', $department);
        $mission = $this->createMission($user, $department);
        $workflow = $this->createWorkflowTemplate($department, 'raci');
        $raciTemplate = $this->createRaciTemplate($department);
        $role = $raciTemplate->roles()->first();

        $stageA = $this->createStage($workflow, [
            'name' => 'Affectation RACI',
            'code' => 'RACI_A',
            'stage_type' => 'raci_assignment',
            'execution_mode' => 'raci',
            'component_key' => 'raci_stage',
            'raci_template_id' => $raciTemplate->id,
        ]);

        $stageB = $this->createStage($workflow, [
            'name' => 'Suite',
            'code' => 'NEXT_STAGE',
            'sort_order' => 1,
        ]);

        WorkflowTransition::query()->create([
            'workflow_template_id' => $workflow->id,
            'from_stage_id' => $stageA->id,
            'to_stage_id' => $stageB->id,
            'is_automatic' => false,
        ]);

        $instance = app(WorkflowExecutionService::class)->startWorkflow($mission, $workflow, $user);

        $this->actingAs($user)
            ->post(route('workflow-runtime.stage.submit', ['mission' => $mission, 'stage' => $stageA]), [
                'process_label' => 'Execution',
                'raci_role_id' => $role->id,
                'role_type' => 'responsible',
                'responsibility_level' => 'high',
            ])
            ->assertRedirect(route('workflow-runtime.stage', ['mission' => $mission, 'stage' => $stageB]));

        $this->assertDatabaseHas('raci_matrices', [
            'mission_id' => $mission->id,
            'raci_template_id' => $raciTemplate->id,
        ]);
        $this->assertSame($stageB->id, $instance->fresh()->current_stage_id);
    }
}
