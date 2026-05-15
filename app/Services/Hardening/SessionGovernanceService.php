<?php

namespace App\Services\Hardening;

use App\Models\User;
use App\Services\Tenant\TenantIsolationService;
use App\Services\Tenant\TenantSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SessionGovernanceService
{
    public function __construct(
        private TenantIsolationService $tenants,
        private TenantSecurityService $tenantSecurity,
    ) {}

    public function bindSession(User $user, Request $request): void
    {
        $context = $this->tenants->current($user);

        Session::put('enterprise.session_bound_at', now()->toIso8601String());
        Session::put('enterprise.session_ip', $request->ip());
        Session::put('enterprise.session_user_agent', $request->userAgent());
        Session::put('enterprise.tenant_key', $context->tenantKey());
    }

    public function validateSession(User $user, Request $request): bool
    {
        $policy = $this->tenantSecurity->policyFor($this->tenants->current($user));

        if ($policy !== null && $policy->strict_session_binding) {
            $boundIp = Session::get('enterprise.session_ip');
            if ($boundIp !== null && $boundIp !== $request->ip()) {
                return false;
            }
        }

        $maxMinutes = $this->tenantSecurity->sessionMaxMinutes($this->tenants->current($user));
        $boundAt = Session::get('enterprise.session_bound_at');

        if ($boundAt !== null) {
            $elapsed = now()->diffInMinutes($boundAt);
            if ($elapsed > $maxMinutes) {
                return false;
            }
        }

        return true;
    }
}
