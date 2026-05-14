<?php

namespace Tests\Feature;

use App\Domain\Risk\Enums\RiskLifecycleStatus;
use App\Models\IdentifiedRisk;
use App\Models\Risque;
use App\Services\Intelligence\EnterpriseRiskIntelligenceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsEnterpriseGovernanceContext;
use Tests\TestCase;

class EnterpriseRiskIntelligenceTest extends TestCase
{
    use BuildsEnterpriseGovernanceContext;
    use RefreshDatabase;

    public function test_enterprise_risk_intelligence_aggregates_trends_correlations_and_heatmaps(): void
    {
        $deptA = $this->governanceDepartment('INTA');
        $deptB = $this->governanceDepartment('INTB');
        $role = $this->governanceRole('inspecteur_services');

        $userA = \App\Models\User::factory()->create([
            'department_id' => $deptA->id,
            'role_id' => $role->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);
        $userB = \App\Models\User::factory()->create([
            'department_id' => $deptB->id,
            'role_id' => $role->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);

        $missionA = $this->governanceMission($deptA, $userA);
        $missionB = $this->governanceMission($deptB, $userB);

        IdentifiedRisk::query()->create([
            'mission_id' => $missionA->id,
            'title' => 'Cyber access gap',
            'description' => 'cyber access gap on privileged accounts',
            'category' => 'cyber',
            'probability' => 4,
            'impact' => 5,
            'criticality' => 'critical',
            'lifecycle_status' => RiskLifecycleStatus::UnderReview->value,
            'owner_department_id' => $deptA->id,
        ]);

        IdentifiedRisk::query()->create([
            'mission_id' => $missionB->id,
            'title' => 'Cyber access weakness',
            'description' => 'cyber access weakness in network controls',
            'category' => 'cyber',
            'probability' => 3,
            'impact' => 4,
            'criticality' => 'high',
            'lifecycle_status' => RiskLifecycleStatus::Detected->value,
            'owner_department_id' => $deptB->id,
        ]);

        Risque::query()->create([
            'description' => 'cyber access gap promoted',
            'risk_uuid' => 'risk-a',
            'risk_reference' => 'RISK-A',
            'promotion_signature' => 'sig-a',
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
            'target_department_id' => $deptB->id,
            'cross_department' => true,
            'shared' => true,
            'detected_at' => Carbon::now()->subMonth(),
        ]);

        Risque::query()->create([
            'description' => 'cyber access weakness promoted',
            'risk_uuid' => 'risk-b',
            'risk_reference' => 'RISK-B',
            'promotion_signature' => 'sig-b',
            'impact_inherent' => 4,
            'probabilite_inherent' => 4,
            'score_inherent' => 16,
            'inherent_score' => 16,
            'impact_residuel' => 3,
            'probabilite_residuel' => 3,
            'score_residuel' => 9,
            'residual_score' => 9,
            'lifecycle_status' => RiskLifecycleStatus::Mitigated->value,
            'criticality' => 'high',
            'owner_department_id' => $deptB->id,
            'source_department_id' => $deptB->id,
            'cross_department' => false,
            'shared' => false,
            'detected_at' => Carbon::now()->subWeeks(2),
        ]);

        $snapshot = app(EnterpriseRiskIntelligenceService::class)->snapshot();

        $this->assertNotEmpty($snapshot['correlations']);
        $this->assertNotEmpty($snapshot['departments']);
        $this->assertNotEmpty($snapshot['trends']);
        $this->assertArrayHasKey('score', $snapshot['maturity']);
        $this->assertArrayHasKey('heatmap', $snapshot['national_heatmap']);
    }
}
