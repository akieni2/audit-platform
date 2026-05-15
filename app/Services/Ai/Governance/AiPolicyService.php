<?php

namespace App\Services\Ai\Governance;

use App\Models\User;
use App\Services\Tenant\TenantSecurityService;
use App\Services\Tenant\TenantIsolationService;

class AiPolicyService
{
    public function __construct(
        private TenantIsolationService $tenants,
        private TenantSecurityService $tenantSecurity,
    ) {}

    public function allowsModule(User $user, string $module): bool
    {
        if (! config('ai_copilot.tenant_isolation', true)) {
            return true;
        }

        $context = $this->tenants->current($user);

        return $this->tenantSecurity->moduleAllowed($context, $module);
    }
}
