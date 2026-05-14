<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Entretien;
use App\Models\Mission;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireSection;
use App\Models\QuestionnaireTemplate;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkflowStage;
use App\Models\WorkflowTemplate;
use App\Models\WorkflowTransition;
use App\Services\Questionnaires\QuestionnaireRuntimeService;
use App\Services\Workflow\WorkflowCompatibilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class DynamicQuestionnaireWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_questionnaire_stage_is_resolved_through_linked_template_and_syncs_legacy_mission_flow(): void
    {
        $user = $this->inspecteurNational();
        $department = $this->department();
        $mission = Mission::query()->create([
            'organisation' => 'Organisation Questionnaire',
            'description' => 'Mission questionnaire',
            'date_debut' => Carbon::today(),
            'date_fin' => null,
            'auditeur_id' => $user->id,
            'department_id' => $department->id,
            'mission_status' => Mission::STATUS_EN_COURS,
        ]);

        $service = $this->missionService($mission, $user);

        $questionnaire = QuestionnaireTemplate::query()->create([
            'name' => 'Questionnaire Workflow',
            'slug' => 'questionnaire-workflow',
            'description' => 'Questionnaire publié',
            'active' => true,
            'version' => 1,
            'lifecycle_status' => QuestionnaireTemplate::STATUS_PUBLISHED,
        ]);

        $section = QuestionnaireSection::query()->create([
            'questionnaire_template_id' => $questionnaire->id,
            'title' => 'Section A',
            'sort_order' => 0,
        ]);

        $question = QuestionnaireQuestion::query()->create([
            'questionnaire_section_id' => $section->id,
            'code' => 'Q1',
            'question' => 'Le contrôle existe-t-il ?',
            'question_type' => QuestionnaireQuestion::TYPE_TEXT,
            'required' => true,
            'allows_observation' => true,
            'allows_risk_detection' => false,
            'sort_order' => 0,
            'active' => true,
            'metadata' => ['options' => []],
        ]);

        $workflow = WorkflowTemplate::query()->create([
            'department_id' => $department->id,
            'name' => 'Workflow Questionnaire Département',
            'slug' => 'workflow-questionnaire-departement',
            'code' => 'WF_Q_DEPT',
            'active' => true,
            'version' => 1,
            'status' => WorkflowTemplate::STATUS_PUBLISHED,
        ]);

        $missionStage = WorkflowStage::query()->create([
            'workflow_template_id' => $workflow->id,
            'name' => 'Mission',
            'code' => 'MISSION',
            'stage_type' => 'mission',
            'execution_mode' => 'automatic',
            'sort_order' => 0,
            'ui_component' => 'stage-card',
            'configuration' => [],
            'configuration_json' => [],
            'position_x' => 0,
            'position_y' => 0,
            'color' => '#0A2A66',
            'icon' => 'mission',
            'is_required' => true,
        ]);

        $questionnaireStage = WorkflowStage::query()->create([
            'workflow_template_id' => $workflow->id,
            'name' => 'Questionnaire',
            'code' => 'QUESTIONNAIRE',
            'stage_type' => 'questionnaire',
            'execution_mode' => 'questionnaire',
            'questionnaire_template_id' => $questionnaire->id,
            'sort_order' => 1,
            'ui_component' => 'questionnaire-stage',
            'configuration' => [],
            'configuration_json' => [],
            'position_x' => 240,
            'position_y' => 0,
            'color' => '#0E7490',
            'icon' => 'questionnaire',
            'is_required' => true,
        ]);

        $riskStage = WorkflowStage::query()->create([
            'workflow_template_id' => $workflow->id,
            'name' => 'Risques',
            'code' => 'RISQUES',
            'stage_type' => 'risk_capture',
            'execution_mode' => 'automatic',
            'sort_order' => 2,
            'ui_component' => 'risk-stage',
            'configuration' => [],
            'configuration_json' => [],
            'position_x' => 480,
            'position_y' => 0,
            'color' => '#7C3AED',
            'icon' => 'risk',
            'is_required' => true,
        ]);

        WorkflowTransition::query()->create([
            'workflow_template_id' => $workflow->id,
            'from_stage_id' => $missionStage->id,
            'to_stage_id' => $questionnaireStage->id,
            'is_automatic' => true,
        ]);

        WorkflowTransition::query()->create([
            'workflow_template_id' => $workflow->id,
            'from_stage_id' => $questionnaireStage->id,
            'to_stage_id' => $riskStage->id,
            'is_automatic' => true,
        ]);

        $compatibility = app(WorkflowCompatibilityService::class);
        $instance = $compatibility->ensureMissionWorkflow($mission, $user);

        $instance->refresh();
        $this->assertSame($workflow->id, $instance->workflow_template_id);
        $this->assertSame($questionnaireStage->id, $instance->current_stage_id);

        $entretien = Entretien::query()->create([
            'mission_id' => $mission->id,
            'service_id' => $service->id,
            'questionnaire_template_id' => $questionnaire->id,
            'responsable_nom' => 'Responsable',
            'role' => 'Chef de service',
            'chef_hierarchique' => 'Directeur',
            'auditeur' => 'Inspecteur',
            'date_entretien' => Carbon::today()->toDateString(),
            'status' => Entretien::STATUS_IN_PROGRESS,
        ]);

        $runtime = app(QuestionnaireRuntimeService::class);
        $runtime->ensureSnapshot($entretien->fresh(), true);

        $request = Request::create('/tests/questionnaire', 'POST');
        $request->setUserResolver(fn () => $user);

        $runtime->recordResponses(
            $entretien->fresh(),
            [[
                'questionnaire_question_id' => $question->id,
                'answer_text' => 'Oui',
                'observation' => 'Réponse saisie',
            ]],
            $user,
            $request,
        );

        $synced = $compatibility->syncMissionWorkflow($mission->fresh(), $user);

        $this->assertSame($riskStage->id, $synced->current_stage_id);
        $this->assertDatabaseHas('workflow_execution_logs', [
            'workflow_instance_id' => $synced->id,
            'event_name' => 'workflow.stage.completed',
            'workflow_stage_id' => $questionnaireStage->id,
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
            'name' => 'Pôle Questionnaire',
            'code' => 'QST',
            'type' => 'pole',
            'description' => 'Questionnaire tests',
            'active' => true,
        ]);
    }

    private function missionService(Mission $mission, User $user)
    {
        return \App\Models\Service::query()->create([
            'mission_id' => $mission->id,
            'code' => 'SVC-QST',
            'nom' => 'Service questionnaire',
            'responsable' => $user->displayName(),
            'description' => 'Service de test',
            'chef_service_user_id' => $user->id,
            'active' => true,
        ]);
    }
}
