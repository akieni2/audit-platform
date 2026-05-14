<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\FormField;
use App\Models\FormTemplate;
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

class DynamicInterviewFormCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_dynamic_interview_form_binds_submission_to_entretien_and_preserves_workflow_progression(): void
    {
        $user = $this->inspecteurNational();
        $department = $this->department();
        $mission = Mission::query()->create([
            'organisation' => 'Organisation Interview',
            'description' => 'Mission interview form',
            'date_debut' => Carbon::today(),
            'date_fin' => null,
            'auditeur_id' => $user->id,
            'department_id' => $department->id,
            'mission_status' => Mission::STATUS_EN_COURS,
        ]);

        $service = \App\Models\Service::query()->create([
            'mission_id' => $mission->id,
            'code' => 'SVC-INT',
            'nom' => 'Service interview',
            'responsable' => $user->displayName(),
            'description' => 'Service test',
            'chef_service_user_id' => $user->id,
            'active' => true,
        ]);

        $formTemplate = FormTemplate::query()->create([
            'name' => 'Formulaire entretien dynamique',
            'slug' => 'formulaire-entretien-dynamique',
            'active' => true,
            'version' => 1,
            'lifecycle_status' => FormTemplate::STATUS_PUBLISHED,
            'signature_hash' => sha1('formulaire-entretien-dynamique'),
        ]);

        FormField::query()->create([
            'form_template_id' => $formTemplate->id,
            'field_key' => 'INTERVIEW_SUMMARY',
            'label' => 'Synthèse entretien',
            'field_type' => FormField::TYPE_TEXTAREA,
            'sort_order' => 0,
            'is_required' => true,
            'active' => true,
        ]);

        $workflow = WorkflowTemplate::query()->create([
            'department_id' => $department->id,
            'name' => 'Workflow entretien dynamique',
            'slug' => 'workflow-entretien-dynamique',
            'code' => 'WF_ENTRETIEN_FORM',
            'active' => true,
            'version' => 1,
            'status' => WorkflowTemplate::STATUS_PUBLISHED,
        ]);

        $interviewStage = WorkflowStage::query()->create([
            'workflow_template_id' => $workflow->id,
            'name' => 'Entretien dynamique',
            'code' => 'ENTRETIEN_DYNAMIQUE',
            'stage_type' => 'form',
            'execution_mode' => 'form',
            'form_template_id' => $formTemplate->id,
            'component_key' => 'dynamic_interview_form',
            'sort_order' => 0,
            'ui_component' => 'dynamic-form',
            'configuration' => [],
            'configuration_json' => [],
            'position_x' => 0,
            'position_y' => 0,
            'color' => '#0E7490',
            'icon' => 'interview',
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
            'from_stage_id' => $interviewStage->id,
            'to_stage_id' => $reportingStage->id,
            'is_automatic' => false,
        ]);

        $instance = app(WorkflowExecutionService::class)->startWorkflow($mission, $workflow, $user);
        $this->actingAs($user);

        $this->post(route('workflow-runtime.stage.submit', ['mission' => $mission, 'stage' => $interviewStage]), [
            'action' => 'complete',
            'INTERVIEW_SUMMARY' => 'Synthèse entretien dynamique.',
        ])->assertRedirect(route('workflow-runtime.stage', ['mission' => $mission, 'stage' => $reportingStage]));

        $entretien = \App\Models\Entretien::query()->where('mission_id', $mission->id)->latest('id')->first();
        $this->assertNotNull($entretien);
        $this->assertSame($service->id, $entretien->service_id);

        $this->assertDatabaseHas('form_submissions', [
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $interviewStage->id,
            'entretien_id' => $entretien->id,
            'status' => 'submitted',
        ]);
        $this->assertSame($reportingStage->id, $instance->fresh()->current_stage_id);
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
            'name' => 'Pôle Interview',
            'code' => 'INT',
            'type' => 'pole',
            'description' => 'Interview tests',
            'active' => true,
        ]);
    }
}
