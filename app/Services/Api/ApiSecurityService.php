<?php

namespace App\Services\Api;

use App\Models\User;
use App\Services\Audit\DataAccessAuditService;
use App\Services\Tenant\TenantSecurityService;
use App\Support\Tenant\ResolvedTenantContext;
use Illuminate\Http\Request;

class ApiSecurityService
{
    public function __construct(
        private TenantSecurityService $tenantSecurity,
        private DataAccessAuditService $accessAudit,
    ) {}

    public function authorizeRequest(User $user, ResolvedTenantContext $context, Request $request): bool
    {
        if (! $this->tenantSecurity->isApiEnabledFor($user, $context)) {
            return false;
        }

        $this->accessAudit->record(
            $user,
            'api_request',
            $request->route()?->getName() ?? 'api',
            null,
            'allowed',
            ['method' => $request->method()],
        );

        return true;
    }
}
