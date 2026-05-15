<?php

namespace Tests\Feature;

use App\Models\Mission;
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
        $user = $this->hardeningInspectorUser($department);
        $tenant = $this->hardeningTenant($department);

        $context = app(TenantResolutionService::class)->resolveForUser($user);

        $this->assertFalse($context->nationalScope);
        $this->assertSame($department->id, $context->departmentId);
        $this->assertSame($tenant->tenant_key, $context->tenantKey());
    }

    public function test_tenant_isolation_blocks_cross_department_mission_access(): void
    {
        $deptA = $this->hardeningDepartment('A');
        $deptB = $this->hardeningDepartment('B');
        $userA = $this->hardeningInspectorUser($deptA);
        $missionB = $this->createMission($userA, $deptB);

        $isolation = app(TenantIsolationService::class);
        $isolation->bind(app(TenantResolutionService::class)->resolveForUser($userA));

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $isolation->assertMissionAccess($missionB, $userA);
    }
}
