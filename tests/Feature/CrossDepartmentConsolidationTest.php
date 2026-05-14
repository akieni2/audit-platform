<?php

namespace Tests\Feature;

use App\Domain\Risk\Enums\RiskLifecycleStatus;
use App\Models\Risque;
use App\Services\Governance\DepartmentConsolidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsEnterpriseGovernanceContext;
use Tests\TestCase;

class CrossDepartmentConsolidationTest extends TestCase
{
    use BuildsEnterpriseGovernanceContext;
    use RefreshDatabase;

    public function test_department_consolidation_service_rolls_up_enterprise_governance_metrics(): void
    {
        $deptA = $this->governanceDepartment('CSA');
        $deptB = $this->governanceDepartment('CSB');
        $user = $this->governanceUser($deptA, 'super_admin');

        $methodologyA = $this->governanceMethodology($deptA);
        $methodologyB = $this->governanceMethodology($deptB);
        $this->governanceControlLibrary($deptA, $methodologyA);
        $this->governanceControlLibrary($deptB, $methodologyB);
        $this->governanceWorkflow($deptA);
        $this->governanceWorkflow($deptB);

        Risque::query()->create([
            'description' => 'cross department risk A',
            'risk_uuid' => 'consol-a',
            'risk_reference' => 'CONS-A',
            'promotion_signature' => 'consol-a',
            'impact_inherent' => 5,
            'probabilite_inherent' => 4,
            'score_inherent' => 20,
            'inherent_score' => 20,
            'impact_residuel' => 4,
            'probabilite_residuel' => 3,
            'score_residuel' => 12,
            'residual_score' => 12,
            'lifecycle_status' => RiskLifecycleStatus::Promoted->value,
            'criticality' => 'critical',
            'owner_department_id' => $deptA->id,
            'source_department_id' => $deptA->id,
            'shared' => true,
            'cross_department' => true,
        ]);

        Risque::query()->create([
            'description' => 'cross department risk B',
            'risk_uuid' => 'consol-b',
            'risk_reference' => 'CONS-B',
            'promotion_signature' => 'consol-b',
            'impact_inherent' => 4,
            'probabilite_inherent' => 3,
            'score_inherent' => 12,
            'inherent_score' => 12,
            'impact_residuel' => 3,
            'probabilite_residuel' => 2,
            'score_residuel' => 6,
            'residual_score' => 6,
            'lifecycle_status' => RiskLifecycleStatus::Mitigated->value,
            'criticality' => 'high',
            'owner_department_id' => $deptB->id,
            'source_department_id' => $deptB->id,
            'shared' => false,
            'cross_department' => false,
        ]);

        $snapshot = app(DepartmentConsolidationService::class)->snapshot();

        $this->assertSame(2, $snapshot['totals']['departments']);
        $this->assertCount(2, $snapshot['rows']);
        $this->assertGreaterThanOrEqual(1, $snapshot['totals']['critical_open']);

        $this->actingAs($user)
            ->get(route('enterprise.consolidation'))
            ->assertOk()
            ->assertSee('Consolidation enterprise');
    }
}
