<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsWorkflowRuntimeContext;
use Tests\TestCase;

class WorkflowDesignerUiTest extends TestCase
{
    use BuildsWorkflowRuntimeContext;
    use RefreshDatabase;

    public function test_workflow_designer_renders_visual_canvas_and_properties_panels(): void
    {
        $department = $this->createDepartment('WFD');
        $user = $this->createUser('inspecteur_services', $department);
        $workflow = $this->createWorkflowTemplate($department, 'designer');

        $this->createStage($workflow, [
            'name' => 'Collecte',
            'code' => 'COLLECTE',
            'stage_type' => 'form',
            'execution_mode' => 'form',
            'component_key' => 'dynamic_form',
        ]);

        $this->createStage($workflow, [
            'name' => 'Validation',
            'code' => 'VALIDATION',
            'stage_type' => 'approval',
            'execution_mode' => 'approval',
            'component_key' => 'approval_form',
            'requires_approval' => true,
            'sort_order' => 1,
            'position_x' => 320,
        ]);

        $this->actingAs($user)
            ->get(route('workflow-builder.edit', $workflow))
            ->assertOk()
            ->assertSee('Workflow Builder Enterprise')
            ->assertSee('Canvas workflow')
            ->assertSee('Minimap')
            ->assertSee('Properties panel')
            ->assertSee('Validation visuelle');
    }
}
