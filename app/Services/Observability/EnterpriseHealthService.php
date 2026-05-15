<?php

namespace App\Services\Observability;

use App\Services\Audit\AuditIntegrityService;
use App\Services\Runtime\QueueHealthService;
use App\Services\Tenant\TenantIsolationService;

class EnterpriseHealthService
{
    public function __construct(
        private QueueHealthService $queues,
        private AuditIntegrityService $auditIntegrity,
        private TenantIsolationService $tenants,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $context = $this->tenants->current();

        return [
            'tenant_scope' => $context->scope,
            'tenant_key' => $context->tenantKey(),
            'queues' => $this->queues->snapshot(),
            'audit_integrity' => $this->auditIntegrity->verifyChain(50),
            'status' => 'operational',
        ];
    }
}
