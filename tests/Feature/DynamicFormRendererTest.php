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
use App\Services\Forms\DynamicFormRendererService;
use App\Services\Workflow\WorkflowExecutionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DynamicFormRendererTest extends TestCase
{
    use RefreshDatabase;

    public function test_renderer_applies_default_values_and_conditional_required_rules(): void
    {
        $user = $this->inspecteurNational();
        $department = $this->department();
        [$instance, $stage] = $this->workflowWithDynamicForm($user, $department);

        $renderer = app(DynamicFormRendererService::class);
        $viewData = $renderer->buildViewData($instance->fresh(), $stage->fresh());

        $this->assertSame(false, $viewData['values']['HAS_INCIDENT'] ?? null);
        $this->assertCount(1, $viewData['visible_fields']);
        $this->assertSame('HAS_INCIDENT', $viewData['visible_fields'][0]['field_key']);

        $execution = $instance->stageExecutions()->where('workflow_stage_id', $stage->id)->firstOrFail();
        $request = Request::create('/tests/forms/runtime', 'POST', [
            'action' => 'complete',
            'HAS_INCIDENT' => '1',
        ]);

        $this->expectException(ValidationException::class);
        $renderer->persistSubmission($request, $instance->fresh(), $stage->fresh(), $execution, $user);
    }

    public function test_renderer_persists_submission_snapshot_and_payload(): void
    {
        $user = $this->inspecteurNational();
        $department = $this->department();
        [$instance, $stage] = $this->workflowWithDynamicForm($user, $department);

        $renderer = app(DynamicFormRendererService::class);
        $execution = $instance->stageExecutions()->where('workflow_stage_id', $stage->id)->firstOrFail();
        $request = Request::create('/tests/forms/runtime', 'POST', [
            'action' => 'complete',
            'HAS_INCIDENT' => '1',
            'INCIDENT_DETAILS' => 'Un incident a été signalé.',
        ]);

        $result = $renderer->persistSubmission($request, $instance->fresh(), $stage->fresh(), $execution, $user);

        $this->assertTrue($result['finalized']);
        $this->assertSame('Un incident a été signalé.', data_get($result['payload'], 'fields.INCIDENT_DETAILS'));
        $this->assertDatabaseHas('form_submissions', [
            'id' => $result['submission']->id,
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $stage->id,
            'status' => 'submitted',
        ]);
        $this->assertNotEmpty(data_get($result['submission']->form_snapshot, 'meta.hash'));
    }

    /**
     * @return array{0:\App\Models\WorkflowInstance,1:WorkflowStage}
     */
    private function workflowWithDynamicForm(User $user, Department $department): array
    {
        $mission = Mission::query()->create([
            'organisation' => 'Organisation Renderer',
            'description' => 'Mission renderer',
            'date_debut' => Carbon::today(),
            'date_fin' => null,
            'auditeur_id' => $user->id,
            'department_id' => $department->id,
            'mission_status' => Mission::STATUS_BROUILLON,
        ]);

        $formTemplate = FormTemplate::query()->create([
            'name' => 'Formulaire incident',
            'slug' => 'formulaire-incident',
            'active' => true,
            'version' => 1,
            'lifecycle_status' => FormTemplate::STATUS_PUBLISHED,
            'signature_hash' => sha1('formulaire-incident'),
        ]);

        FormField::query()->create([
            'form_template_id' => $formTemplate->id,
            'field_key' => 'HAS_INCIDENT',
            'label' => 'Incident détecté',
            'field_type' => FormField::TYPE_BOOLEAN,
            'default_value' => 'false',
            'sort_order' => 0,
            'is_required' => true,
            'active' => true,
        ]);

        FormField::query()->create([
            'form_template_id' => $formTemplate->id,
            'field_key' => 'INCIDENT_DETAILS',
            'label' => 'Détails incident',
            'field_type' => FormField::TYPE_TEXTAREA,
            'sort_order' => 1,
            'is_required' => true,
            'active' => true,
            'conditional_rules_json' => [
                'match' => 'all',
                'rules' => [[
                    'depends_on' => 'HAS_INCIDENT',
                    'operator' => 'equals',
                    'value' => '1',
                ]],
            ],
        ]);

        $workflow = WorkflowTemplate::query()->create([
            'department_id' => $department->id,
            'name' => 'Workflow Forms',
            'slug' => 'workflow-forms',
            'code' => 'WF_FORMS',
            'active' => true,
            'version' => 1,
            'status' => WorkflowTemplate::STATUS_PUBLISHED,
        ]);

        $stage = WorkflowStage::query()->create([
            'workflow_template_id' => $workflow->id,
            'name' => 'Collecte',
            'code' => 'COLLECTE',
            'stage_type' => 'form',
            'execution_mode' => 'form',
            'form_template_id' => $formTemplate->id,
            'component_key' => 'dynamic_form',
            'sort_order' => 0,
            'ui_component' => 'dynamic-form',
            'configuration' => [],
            'configuration_json' => [],
            'position_x' => 0,
            'position_y' => 0,
            'color' => '#0A2A66',
            'icon' => 'form',
            'is_required' => true,
        ]);

        $instance = app(WorkflowExecutionService::class)->startWorkflow($mission, $workflow, $user);

        return [$instance, $stage];
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
            'name' => 'Pôle Runtime',
            'code' => 'FRUN',
            'type' => 'pole',
            'description' => 'Runtime tests',
            'active' => true,
        ]);
    }
}
