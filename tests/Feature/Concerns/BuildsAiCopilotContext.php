<?php

namespace Tests\Feature\Concerns;

use App\Models\Department;
use App\Models\TenantContext;
use App\Models\User;
use App\Services\Tenant\TenantIsolationService;
use App\Services\Tenant\TenantResolutionService;
use App\Support\Tenant\ResolvedTenantContext;

trait BuildsAiCopilotContext
{
    use BuildsEnterpriseHardeningContext;

    protected function bindTenantFor(User $user): ResolvedTenantContext
    {
        $context = app(TenantResolutionService::class)->resolveForUser($user);
        app(TenantIsolationService::class)->bind($context);

        return $context;
    }

    protected function ensureAiTenant(Department $department): TenantContext
    {
        return $this->hardeningTenant($department);
    }
}
