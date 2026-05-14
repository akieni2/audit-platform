<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Mission;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkflowStage;
use App\Models\WorkflowTemplate;
use App\Models\WorkflowTransition;
use App\Services\Workflow\WorkflowExecutionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowExecutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execution_service_can_start_complete_and_log_workflow_stages(): void
    {
        $user = $this->inspecteurNational();
        $department = $this->department();

        $mission = Mission::query()->create([
            'organisation' => 'Organisation Workflow',
            'description' => 'Execution workflow',
            'date_debut' => Carbon::today(),
            'date_fin' => null,
            'auditeur_id' => $user->id,
            'department_id' => $department->id,
            'mission_status' => Mission::STATUS_BROUILLON,
        ]);

        $template = WorkflowTemplate::query()->create([
            'department_id' => $department->id,
            'name' => 'Workflow Exécution',
            'slug' => 'workflow-execution',
            'code' => 'WF_EXEC',
            'active' => true,
            'version' => 1,
            'status' => WorkflowTemplate::STATUS_PUBLISHED,
        ]);

        $stageA = WorkflowStage::query()->create([
            'workflow_template_id' => $template->id,
            'name' => 'Collecte manuelle',
            'code' => 'FORM_A',
            'stage_type' => 'custom',
            'execution_mode' => 'manual',
            'sort_order' => 0,
            'ui_component' => 'stage-card',
            'configuration' => [],
            'configuration_json' => [],
            'position_x' => 0,
            'position_y' => 0,
            'color' => '#0A2A66',
            'icon' => 'form',
            'is_required' => true,
        ]);

        $stageB = WorkflowStage::query()->create([
            'workflow_template_id' => $template->id,
            'name' => 'Approbation',
            'code' => 'APPROVAL_B',
            'stage_type' => 'approval',
            'execution_mode' => 'approval',
            'sort_order' => 1,
            'ui_component' => 'stage-card',
            'configuration' => [],
            'configuration_json' => [],
            'position_x' => 240,
            'position_y' => 0,
            'color' => '#0E7490',
            'icon' => 'approval',
            'is_required' => true,
        ]);

        WorkflowTransition::query()->create([
            'workflow_template_id' => $template->id,
            'from_stage_id' => $stageA->id,
            'to_stage_id' => $stageB->id,
            'is_automatic' => false,
        ]);

        $service = app(WorkflowExecutionService::class);
        $instance = $service->startWorkflow($mission, $template, $user);

        $this->assertSame($stageA->id, $instance->current_stage_id);

        $service->startStage($instance, $stageA, $user, ['field' => 'value']);

        $advanced = $service->completeStage($instance->fresh(['currentStage', 'stageExecutions.workflowStage']), $stageA->fresh(), $user, [
            'approved' => false,
            'form' => ['field' => 'value'],
        ]);

        $this->assertSame($stageB->id, $advanced->current_stage_id);
        $this->assertDatabaseHas('workflow_stage_executions', [
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $stageA->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('workflow_execution_logs', [
            'workflow_instance_id' => $instance->id,
            'event_name' => 'workflow.stage.completed',
        ]);
    }

    private function inspecteurNational(): User
    {
        $role = Role::query()->create([
            'slug' => 'inspecteur_services',
            'name' => 'Inspecteur des Services',
            'hierarchy_level' => 100,
            'active' => true,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);
    }

    private function department(): Department
    {
        return Department::query()->create([
            'name' => 'Pôle Exécution',
            'code' => 'EXEC',
            'type' => 'pole',
            'description' => 'Execution tests',
            'active' => true,
        ]);
    }
}
