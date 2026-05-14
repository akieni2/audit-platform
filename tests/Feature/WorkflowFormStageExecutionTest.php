<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\FormField;
use App\Models\FormTemplate;
use App\Models\IdentifiedRisk;
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

class WorkflowFormStageExecutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_stage_submission_persists_payload_advances_workflow_and_bridges_risk_registry(): void
    {
        $user = $this->inspecteurNational();
        $department = $this->department();
        $mission = Mission::query()->create([
            'organisation' => 'Organisation Workflow Forms',
            'description' => 'Mission workflow forms',
            'date_debut' => Carbon::today(),
            'date_fin' => null,
            'auditeur_id' => $user->id,
            'department_id' => $department->id,
            'mission_status' => Mission::STATUS_EN_COURS,
        ]);

        $this->actingAs($user);

        $risk = IdentifiedRisk::query()->create([
            'mission_id' => $mission->id,
            'title' => 'Risque opérationnel',
            'description' => 'Risque initial',
            'criticality' => 'high',
            'lifecycle_status' => 'detected',
            'source_signature' => sha1('risk-'.$mission->id),
            'created_by' => $user->id,
            'validated_by_human' => false,
            'ai_generated' => false,
        ]);

        $formTemplate = FormTemplate::query()->create([
            'name' => 'Formulaire analyse',
            'slug' => 'formulaire-analyse',
            'active' => true,
            'version' => 1,
            'lifecycle_status' => FormTemplate::STATUS_PUBLISHED,
            'signature_hash' => sha1('formulaire-analyse'),
        ]);

        FormField::query()->create([
            'form_template_id' => $formTemplate->id,
            'field_key' => 'SUMMARY',
            'label' => 'Synthèse',
            'field_type' => FormField::TYPE_TEXT,
            'sort_order' => 0,
            'is_required' => true,
            'active' => true,
        ]);

        FormField::query()->create([
            'form_template_id' => $formTemplate->id,
            'field_key' => 'RISKS',
            'label' => 'Risques liés',
            'field_type' => FormField::TYPE_RISK_SELECTOR,
            'sort_order' => 1,
            'is_required' => false,
            'active' => true,
            'configuration_json' => ['multiple' => true],
        ]);

        $workflow = WorkflowTemplate::query()->create([
            'department_id' => $department->id,
            'name' => 'Workflow runtime forms',
            'slug' => 'workflow-runtime-forms',
            'code' => 'WF_RUNTIME_FORMS',
            'active' => true,
            'version' => 1,
            'status' => WorkflowTemplate::STATUS_PUBLISHED,
        ]);

        $formStage = WorkflowStage::query()->create([
            'workflow_template_id' => $workflow->id,
            'name' => 'Analyse dynamique',
            'code' => 'ANALYSE_DYNAMIQUE',
            'stage_type' => 'form',
            'execution_mode' => 'form',
            'form_template_id' => $formTemplate->id,
            'component_key' => 'risk_capture_form',
            'sort_order' => 0,
            'ui_component' => 'dynamic-form',
            'configuration' => [],
            'configuration_json' => [
                'risk_selector_action' => 'submit_for_review',
            ],
            'position_x' => 0,
            'position_y' => 0,
            'color' => '#0A2A66',
            'icon' => 'form',
            'is_required' => true,
        ]);

        $reportingStage = WorkflowStage::query()->create([
            'workflow_template_id' => $workflow->id,
            'name' => 'Reporting',
            'code' => 'REPORTING',
            'stage_type' => 'reporting',
            'execution_mode' => 'manual',
            'component_key' => 'system_stage',
            'sort_order' => 1,
            'ui_component' => 'stage-card',
            'configuration' => [],
            'configuration_json' => [],
            'position_x' => 240,
            'position_y' => 0,
            'color' => '#1D4ED8',
            'icon' => 'report',
            'is_required' => true,
        ]);

        WorkflowTransition::query()->create([
            'workflow_template_id' => $workflow->id,
            'from_stage_id' => $formStage->id,
            'to_stage_id' => $reportingStage->id,
            'is_automatic' => false,
        ]);

        $instance = app(WorkflowExecutionService::class)->startWorkflow($mission, $workflow, $user);

        $this->post(route('workflow-runtime.stage.submit', ['mission' => $mission, 'stage' => $formStage]), [
            'action' => 'complete',
            'SUMMARY' => 'Analyse terminée.',
            'RISKS' => [$risk->id],
        ])->assertRedirect(route('workflow-runtime.stage', ['mission' => $mission, 'stage' => $reportingStage]));

        $this->assertDatabaseHas('form_submissions', [
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $formStage->id,
            'status' => 'submitted',
        ]);
        $this->assertDatabaseHas('workflow_stage_executions', [
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $formStage->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('workflow_execution_logs', [
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $formStage->id,
            'event_name' => 'workflow.stage.completed',
        ]);
        $this->assertSame(
            $reportingStage->id,
            $instance->fresh()->current_stage_id
        );
        $this->assertSame(
            'Analyse terminée.',
            data_get($instance->fresh()->metadata, 'forms.ANALYSE_DYNAMIQUE.SUMMARY')
        );

        $risk->refresh();
        $this->assertSame('under_review', $risk->lifecycle_status);
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
            'name' => 'Pôle Workflow Forms',
            'code' => 'WFF',
            'type' => 'pole',
            'description' => 'Workflow forms tests',
            'active' => true,
        ]);
    }
}
