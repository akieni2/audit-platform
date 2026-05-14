<?php

namespace Tests\Feature;

use App\Services\Methodologies\MethodologyWorkflowMappingService;
use App\Services\Runtime\RuntimeRecommendationService;
use App\Services\Workflow\WorkflowExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsEnterpriseGovernanceContext;
use Tests\Feature\Concerns\BuildsWorkflowRuntimeContext;
use Tests\TestCase;

class RuntimeRecommendationTest extends TestCase
{
    use BuildsEnterpriseGovernanceContext;
    use BuildsWorkflowRuntimeContext;
    use RefreshDatabase;

    public function test_runtime_recommendation_service_suggests_controls_for_active_stage(): void
    {
        $department = $this->governanceDepartment('RTR');
        $user = $this->governanceUser($department, 'inspecteur_services');
        $mission = $this->governanceMission($department, $user);
        $workflow = $this->governanceWorkflow($department);
        $stage = $this->createStage($workflow, [
            'name' => 'Cyber Review',
            'code' => 'CYBER',
            'stage_type' => 'risk_capture',
            'component_key' => 'risk_capture_form',
        ]);

        $methodology = $this->governanceMethodology($department);
        $category = $this->governanceMethodologyCategory($methodology);
        $control = $this->governanceMethodologyControl($methodology, $category, ['control_reference' => 'CYB-01']);
        $requirement = $this->governanceMethodologyRequirement($methodology, $category, $control);
        $taxonomy = $this->governanceTaxonomy($department);
        $term = $this->governanceTaxonomyTerm($taxonomy, ['name' => 'Cyber', 'alias_terms' => ['cyber', 'cybersécurité']]);
        $library = $this->governanceControlLibrary($department, $methodology);
        $this->governanceControlMeasure($library, $control, $term, $department, ['code' => 'CYB-CONTROL']);

        $workflow->update(['methodology_template_id' => $methodology->id]);
        app(MethodologyWorkflowMappingService::class)->createMapping($methodology, [
            'mapping_type' => 'stage_bundle',
            'methodology_control_id' => $control->id,
            'methodology_requirement_id' => $requirement->id,
            'workflow_template_id' => $workflow->id,
            'workflow_stage_id' => $stage->id,
            'control_library_id' => $library->id,
            'risk_category' => 'cyber',
            'taxonomy_term_id' => $term->id,
        ], $user);

        $instance = app(WorkflowExecutionService::class)->startWorkflow($mission, $workflow, $user);
        $recommendations = app(RuntimeRecommendationService::class)->forStage(
            $instance->fresh(['mission.department', 'workflowTemplate.methodologyTemplate', 'currentStage']),
            $stage
        );

        $this->assertNotNull($recommendations['methodology']);
        $this->assertNotEmpty($recommendations['risk_suggestions']['recommendations']);
        $this->assertGreaterThan(0, $recommendations['intelligent_score']['confidence']);
    }
}
