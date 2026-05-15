<?php

namespace App\Services\Tenant;

use App\Models\TenantSecurityPolicy;
use App\Models\User;
use App\Support\Tenant\ResolvedTenantContext;

class TenantSecurityService
{
    public function policyFor(ResolvedTenantContext $context): ?TenantSecurityPolicy
    {
        return $context->tenant?->securityPolicy;
    }

    public function moduleAllowed(ResolvedTenantContext $context, string $module): bool
    {
        $policy = $this->policyFor($context);

        if ($policy === null || $context->nationalScope) {
            return true;
        }

        $allowed = $policy->allowed_modules;

        if ($allowed === null || $allowed === []) {
            return true;
        }

        return in_array($module, $allowed, true);
    }

    public function requiresSignedActions(ResolvedTenantContext $context): bool
    {
        if (! config('enterprise_hardening.signed_runtime_actions', true)) {
            return false;
        }

        return $this->policyFor($context)?->signed_actions_required ?? true;
    }

    public function sessionMaxMinutes(ResolvedTenantContext $context): int
    {
        return $this->policyFor($context)?->max_session_minutes
            ?? (int) config('session.lifetime', 120);
    }

    public function isApiEnabledFor(User $user, ResolvedTenantContext $context): bool
    {
        if ($context->nationalScope) {
            return true;
        }

        return $this->policyFor($context)?->api_access_enabled ?? true;
    }
}
