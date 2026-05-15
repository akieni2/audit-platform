<?php

namespace App\Http\Middleware;

use App\Services\Tenant\TenantIsolationService;
use App\Services\Tenant\TenantResolutionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function __construct(
        private TenantResolutionService $resolution,
        private TenantIsolationService $isolation,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('enterprise_hardening.tenant_isolation', true)) {
            return $next($request);
        }

        $context = $this->resolution->resolveForUser($request->user());
        $this->isolation->bind($context);
        $request->attributes->set('tenant_context', $context);

        return $next($request);
    }
}
