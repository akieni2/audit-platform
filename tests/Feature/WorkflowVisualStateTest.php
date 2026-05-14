<?php

namespace Tests\Feature;

use App\Domain\Workflow\Enums\WorkflowVisualState;
use App\Services\Workflow\WorkflowExecutionService;
use App\Services\Workflow\WorkflowVisualStateResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsWorkflowRuntimeContext;
use Tests\TestCase;

class WorkflowVisualStateTest extends TestCase
{
    use BuildsWorkflowRuntimeContext;
    use RefreshDatabase;

    public function test_visual_state_resolver_marks_current_approval_stage_as_awaiting_approval(): void
    {
        $department = $this->createDepartment('VST');
        $user = $this->createUser('inspecteur_services', $department);
        $mission = $this->createMission($user, $department);
        $workflow = $this->createWorkflowTemplate($department, 'visual-state');

        $stage = $this->createStage($workflow, [
            'name' => 'Approbation',
            'code' => 'APPRO',
            'stage_type' => 'approval',
            'execution_mode' => 'approval',
            'component_key' => 'approval_form',
            'requires_approval' => true,
        ]);

        $instance = app(WorkflowExecutionService::class)->startWorkflow($mission, $workflow, $user);
        $instance = $instance->fresh(['stageExecutions']);
        $execution = $instance->stageExecutions->first();

        $state = app(WorkflowVisualStateResolver::class)->resolve($instance, $stage, $execution);

        $this->assertSame(WorkflowVisualState::AwaitingApproval, $state);
        $this->assertSame('En attente d’approbation', $state->label());
    }
}
