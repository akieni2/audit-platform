<?php

namespace Tests\Feature;

use App\Services\Workflow\WorkflowExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsWorkflowRuntimeContext;
use Tests\TestCase;

class DynamicFormWizardTest extends TestCase
{
    use BuildsWorkflowRuntimeContext;
    use RefreshDatabase;

    public function test_dynamic_form_runtime_renders_wizard_autosave_and_validation_summary(): void
    {
        $department = $this->createDepartment('DFW');
        $user = $this->createUser('inspecteur_services', $department);
        $mission = $this->createMission($user, $department);
        $workflow = $this->createWorkflowTemplate($department, 'wizard');

        $stage = $this->createStage($workflow, [
            'name' => 'Questionnaire visuel',
            'code' => 'FORM_WIZARD',
            'stage_type' => 'form',
            'execution_mode' => 'form',
            'component_key' => 'dynamic_form',
            'form_schema_json' => [
                'fields' => [
                    [
                        'field_key' => 'name',
                        'label' => 'Nom',
                        'field_type' => 'text',
                        'is_required' => true,
                        'configuration' => ['wizard_step' => 'Informations'],
                    ],
                    [
                        'field_key' => 'evidence',
                        'label' => 'Pièce jointe',
                        'field_type' => 'file',
                        'configuration' => ['wizard_step' => 'Justificatifs'],
                    ],
                ],
            ],
        ]);

        app(WorkflowExecutionService::class)->startWorkflow($mission, $workflow, $user);

        $this->actingAs($user)
            ->get(route('workflow-runtime.stage', ['mission' => $mission, 'stage' => $stage]))
            ->assertOk()
            ->assertSee('Runtime dynamique')
            ->assertSee('Autosave')
            ->assertSee('Validation temps réel')
            ->assertSee('Pièces jointes visuelles')
            ->assertSee('Étape suivante');
    }
}
