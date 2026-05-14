<?php

namespace Tests\Feature;

use App\Models\WorkflowTransition;
use App\Services\Workflow\WorkflowExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsWorkflowRuntimeContext;
use Tests\TestCase;

class WorkflowRuntimeUiTest extends TestCase
{
    use BuildsWorkflowRuntimeContext;
    use RefreshDatabase;

    public function test_runtime_visual_page_renders_progress_graph_timeline_and_stage_panels(): void
    {
        $department = $this->createDepartment('RTUI');
        $user = $this->createUser('inspecteur_services', $department);
        $mission = $this->createMission($user, $department);
        $workflow = $this->createWorkflowTemplate($department, 'visual');

        $stageA = $this->createStage($workflow, [
            'name' => 'Collecte',
            'code' => 'COLLECTE',
            'stage_type' => 'form',
            'execution_mode' => 'form',
            'component_key' => 'dynamic_form',
        ]);
        $stageB = $this->createStage($workflow, [
            'name' => 'Validation',
            'code' => 'VALIDATION',
            'stage_type' => 'approval',
            'execution_mode' => 'approval',
            'component_key' => 'approval_form',
            'requires_approval' => true,
            'sort_order' => 1,
        ]);

        WorkflowTransition::query()->create([
            'workflow_template_id' => $workflow->id,
            'from_stage_id' => $stageA->id,
            'to_stage_id' => $stageB->id,
            'is_automatic' => false,
        ]);

        app(WorkflowExecutionService::class)->startWorkflow($mission, $workflow, $user);

        $this->actingAs($user)
            ->get(route('workflow-runtime.show', $mission))
            ->assertOk()
            ->assertSee('Visual Workflow Runtime')
            ->assertSee('Parcours des étapes')
            ->assertSee('Workflow Graph')
            ->assertSee('Journal chronologique')
            ->assertSee('Collecte')
            ->assertSee('Validation');
    }
}
