<?php

namespace Tests\Feature;

use App\Models\WorkflowTransition;
use App\Services\Workflow\WorkflowExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsWorkflowRuntimeContext;
use Tests\TestCase;

class WorkflowSwotIntegrationTest extends TestCase
{
    use BuildsWorkflowRuntimeContext;
    use RefreshDatabase;

    public function test_workflow_swot_stage_can_complete_and_advance_runtime(): void
    {
        $department = $this->createDepartment('WSI');
        $user = $this->createUser('inspecteur_services', $department);
        $mission = $this->createMission($user, $department);
        $workflow = $this->createWorkflowTemplate($department, 'swot');
        $swotTemplate = $this->createSwotTemplate($department);

        $stageA = $this->createStage($workflow, [
            'name' => 'Analyse SWOT',
            'code' => 'SWOT_A',
            'stage_type' => 'swot_analysis',
            'execution_mode' => 'swot',
            'component_key' => 'swot_stage',
            'swot_template_id' => $swotTemplate->id,
        ]);

        $stageB = $this->createStage($workflow, [
            'name' => 'Validation manuelle',
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
            ->post(route('workflow-runtime.stage.submit', ['mission' => $mission, 'stage' => $stageA]), [])
            ->assertRedirect(route('workflow-runtime.stage', ['mission' => $mission, 'stage' => $stageB]));

        $this->assertDatabaseHas('swot_analyses', [
            'mission_id' => $mission->id,
            'swot_template_id' => $swotTemplate->id,
        ]);
        $this->assertSame($stageB->id, $instance->fresh()->current_stage_id);
    }
}
