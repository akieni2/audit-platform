<?php

namespace Tests\Feature;

use App\Services\Hardening\DataEncryptionService;
use App\Services\Hardening\RuntimeIntegrityService;
use App\Services\Hardening\SecurityAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Feature\Concerns\BuildsEnterpriseHardeningContext;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use BuildsEnterpriseHardeningContext;
    use RefreshDatabase;

    public function test_runtime_action_signature_is_deterministic(): void
    {
        $department = $this->hardeningDepartment();
        $user = $this->hardeningInspectorUser($department);
        $this->hardeningTenant($department);
        app(\App\Services\Tenant\TenantIsolationService::class)
            ->bind(app(\App\Services\Tenant\TenantResolutionService::class)->resolveForUser($user));
        $request = Request::create('/test', 'POST');

        $service = app(SecurityAuditService::class);
        $sig1 = $service->runtimeActionSigned($user, $request, 'approve', ['stage_id' => 1]);
        $sig2 = $service->runtimeActionSigned($user, $request, 'approve', ['stage_id' => 1]);

        $this->assertSame($sig1, $sig2);
        $this->assertTrue(app(RuntimeIntegrityService::class)->verifySignature($sig1, $user, $request, 'approve', ['stage_id' => 1]));
    }

    public function test_sensitive_payload_encryption_roundtrip(): void
    {
        $payload = ['risk_level' => 'high', 'note' => 'classified'];
        $encryption = app(DataEncryptionService::class);

        $encrypted = $encryption->encryptPayload($payload);
        $this->assertSame($payload, $encryption->decryptPayload($encrypted));
    }
}
