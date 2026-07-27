<?php

namespace Tests\Feature;

use App\Models\MissionTeamMember;
use App\Services\Tenant\TenantIsolationService;
use App\Services\Tenant\TenantResolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsEnterpriseHardeningContext;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use BuildsEnterpriseHardeningContext;
    use RefreshDatabase;

    public function test_tenant_context_is_resolved_for_department_user(): void
    {
        $department = $this->hardeningDepartment('TEN');
        $user = $this->createUser('inspecteur_verificateur', $department, 50);
        $tenant = $this->hardeningTenant($department);

        $context = app(TenantResolutionService::class)->resolveForUser($user);

        $this->assertFalse($context->nationalScope);
        $this->assertSame($department->id, $context->departmentId);
        $this->assertSame($tenant->tenant_key, $context->tenantKey());
    }

    public function test_tenant_isolation_allows_cross_department_mission_lead(): void
    {
        $pole = $this->hardeningDepartment('PI');
        $auditedDepartment = $this->hardeningDepartment('DSI');
        $missionLead = $this->createUser('inspecteur_verificateur', $pole, 50);
        $mission = $this->createMission($missionLead, $auditedDepartment);

        MissionTeamMember::query()->create([
            'mission_id' => $mission->id,
            'user_id' => $missionLead->id,
            'mission_role' => MissionTeamMember::ROLE_CHEF_MISSION,
            'is_lead' => true,
            'assigned_at' => now(),
            'assigned_by' => $missionLead->id,
        ]);

        $isolation = app(TenantIsolationService::class);
        $isolation->bind(app(TenantResolutionService::class)->resolveForUser($missionLead));
        $isolation->assertMissionAccess($mission, $missionLead);

        $this->assertTrue($missionLead->can('view', $mission));
        $this->actingAs($missionLead)->get(route('ai.mission', $mission))->assertOk();
    }

    public function test_tenant_isolation_blocks_cross_department_mission_access(): void
    {
        $deptA = $this->hardeningDepartment('A');
        $deptB = $this->hardeningDepartment('B');
        $userA = $this->createUser('inspecteur_verificateur', $deptA, 50);
        $missionB = $this->createMission($userA, $deptB);

        $isolation = app(TenantIsolationService::class);
        $isolation->bind(app(TenantResolutionService::class)->resolveForUser($userA));

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $isolation->assertMissionAccess($missionB, $userA);
    }
}
