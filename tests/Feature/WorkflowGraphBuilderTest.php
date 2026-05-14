<?php

namespace Tests\Feature;

use App\Models\WorkflowTransition;
use App\Services\Workflow\WorkflowExecutionService;
use App\Services\Workflow\WorkflowGraphBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsWorkflowRuntimeContext;
use Tests\TestCase;

class WorkflowGraphBuilderTest extends TestCase
{
    use BuildsWorkflowRuntimeContext;
    use RefreshDatabase;

    public function test_graph_builder_returns_nodes_and_edges_for_visual_runtime(): void
    {
        $department = $this->createDepartment('GRF');
        $user = $this->createUser('inspecteur_services', $department);
        $mission = $this->createMission($user, $department);
        $workflow = $this->createWorkflowTemplate($department, 'graph');

        $stageA = $this->createStage($workflow, [
            'name' => 'Analyse',
            'code' => 'ANALYSE',
            'position_x' => 0,
            'position_y' => 20,
        ]);
        $stageB = $this->createStage($workflow, [
            'name' => 'Validation',
            'code' => 'VALIDATION',
            'position_x' => 260,
            'position_y' => 20,
            'sort_order' => 1,
        ]);

        WorkflowTransition::query()->create([
            'workflow_template_id' => $workflow->id,
            'from_stage_id' => $stageA->id,
            'to_stage_id' => $stageB->id,
            'is_automatic' => false,
        ]);

        $execution = app(WorkflowExecutionService::class);
        $instance = $execution->startWorkflow($mission, $workflow, $user);
        $execution->completeStage($instance->fresh(['currentStage', 'stageExecutions.workflowStage']), $stageA, $user);

        $graph = app(WorkflowGraphBuilderService::class)->build($instance->fresh(['workflowTemplate.transitions', 'workflowTemplate.stages', 'stageExecutions.workflowStage']));

        $this->assertCount(2, $graph['nodes']);
        $this->assertCount(1, $graph['edges']);
        $this->assertSame('Validation', $graph['nodes'][1]['label']);
        $this->assertTrue($graph['edges'][0]['active_path']);
    }
}
