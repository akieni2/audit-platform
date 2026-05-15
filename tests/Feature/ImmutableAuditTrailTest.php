<?php

namespace Tests\Feature;

use App\Models\ImmutableAuditEvent;
use App\Services\Audit\AuditIntegrityService;
use App\Services\Audit\ImmutableAuditTrailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Feature\Concerns\BuildsEnterpriseHardeningContext;
use Tests\TestCase;

class ImmutableAuditTrailTest extends TestCase
{
    use BuildsEnterpriseHardeningContext;
    use RefreshDatabase;

    public function test_immutable_audit_chain_records_events(): void
    {
        $department = $this->hardeningDepartment();
        $user = $this->hardeningInspectorUser($department);
        $this->hardeningTenant($department);
        $this->actingAs($user);
        app(\App\Services\Tenant\TenantIsolationService::class)
            ->bind(app(\App\Services\Tenant\TenantResolutionService::class)->resolveForUser($user));

        $trail = app(ImmutableAuditTrailService::class);
        $trail->record('workflow_transition', 'workflows', 'Test', $user, Request::create('/'), ['ok' => true]);
        $trail->record('workflow_transition', 'workflows', 'Test 2', $user, Request::create('/'), ['ok' => true]);

        $this->assertSame(2, ImmutableAuditEvent::query()->count());
        $this->assertTrue(app(AuditIntegrityService::class)->verifyChain()['verified']);
    }
}
