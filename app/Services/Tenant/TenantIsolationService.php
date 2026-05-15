<?php

namespace App\Services\Tenant;

use App\Models\Mission;
use App\Models\User;
use App\Support\Tenant\ResolvedTenantContext;
use Illuminate\Auth\Access\AuthorizationException;

class TenantIsolationService
{
    public function __construct(
        private TenantResolutionService $resolution,
        private TenantScopeService $scope,
    ) {}

    public function current(?User $user = null): ResolvedTenantContext
    {
        if (app()->bound(ResolvedTenantContext::class)) {
            return app(ResolvedTenantContext::class);
        }

        return $this->resolution->resolveForUser($user ?? auth()->user());
    }

    public function bind(ResolvedTenantContext $context): void
    {
        app()->instance(ResolvedTenantContext::class, $context);
    }

    public function assertMissionAccess(Mission $mission, ?User $user = null): void
    {
        if (! config('enterprise_hardening.tenant_isolation', true)) {
            return;
        }

        $context = $this->current($user);

        if ($this->scope->canAccessMission($context, $mission)) {
            return;
        }

        throw new AuthorizationException('Accès interdit hors périmètre tenant.');
    }
}
