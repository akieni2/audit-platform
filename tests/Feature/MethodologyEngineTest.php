<?php

namespace Tests\Feature;

use App\Services\Methodologies\MethodologyWorkflowMappingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsEnterpriseGovernanceContext;
use Tests\TestCase;

class MethodologyEngineTest extends TestCase
{
    use BuildsEnterpriseGovernanceContext;
    use RefreshDatabase;

    public function test_methodology_engine_resolves_multi_framework_mapping_stack(): void
    {
        $department = $this->governanceDepartment('METH');
        $user = $this->governanceUser($department, 'super_admin');
        $workflow = $this->governanceWorkflow($department);
        $methodology = $this->governanceMethodology($department, [
            'default_workflow_template_id' => $workflow->id,
            'is_global' => true,
        ]);
        $category = $this->governanceMethodologyCategory($methodology);
        $control = $this->governanceMethodologyControl($methodology, $category);
        $requirement = $this->governanceMethodologyRequirement($methodology, $category, $control);

        $service = app(MethodologyWorkflowMappingService::class);
        $service->createMapping($methodology, [
            'mapping_type' => 'default_stack',
            'methodology_control_id' => $control->id,
            'methodology_requirement_id' => $requirement->id,
            'workflow_template_id' => $workflow->id,
            'risk_category' => 'cybersecurity',
        ], $user);

        $stack = $service->resolveStack($methodology, $department->id);

        $this->assertSame(1, $stack['workflows']->count());
        $this->assertSame('cybersecurity', $stack['risk_categories']->first());
        $this->assertSame(1, $service->coverage($methodology)['controls']);

        $this->actingAs($user)
            ->get(route('enterprise.methodologies'))
            ->assertOk()
            ->assertSee('Méthodologies enterprise')
            ->assertSee($methodology->name);
    }
}
