<?php

namespace Tests\Feature;

use App\Models\WorkflowTransition;
use App\Services\Workflow\WorkflowExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsWorkflowRuntimeContext;
use Tests\TestCase;

class WorkflowApprovalFlowTest extends TestCase
{
    use BuildsWorkflowRuntimeContext;
    use RefreshDatabase;

    public function test_runtime_approval_action_advances_the_workflow(): void
    {
        $department = $this->createDepartment('APP');
        $user = $this->createUser('inspecteur_services', $department);
        $mission = $this->createMission($user, $department);
        $workflow = $this->createWorkflowTemplate($department, 'approval');

        $approval = $this->createStage($workflow, [
            'name' => 'Validation IS',
            'code' => 'VALID_IS',
            'stage_type' => 'approval',
            'execution_mode' => 'approval',
            'component_key' => 'approval_form',
            'requires_approval' => true,
        ]);
        $reporting = $this->createStage($workflow, [
            'name' => 'Reporting',
            'code' => 'REPORTING',
            'sort_order' => 1,
        ]);

        WorkflowTransition::query()->create([
            'workflow_template_id' => $workflow->id,
            'from_stage_id' => $approval->id,
            'to_stage_id' => $reporting->id,
            'is_automatic' => false,
        ]);

        $instance = app(WorkflowExecutionService::class)->startWorkflow($mission, $workflow, $user);

        $response = $this->actingAs($user)->post(route('workflow-runtime.transition', $mission), [
            'action' => 'approve',
            'stage_id' => $approval->id,
            'comment' => 'Approuvé par test.',
        ]);

        $response->assertRedirect(route('workflow-runtime.show', $mission));

        $instance->refresh();
        $this->assertSame($reporting->id, $instance->current_stage_id);
        $this->assertDatabaseHas('workflow_stage_executions', [
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $approval->id,
            'status' => 'completed',
        ]);
    }
}
