<?php

namespace App\Http\Middleware;

use App\Models\Mission;
use App\Services\Tenant\TenantIsolationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTenantIsolation
{
    public function __construct(private TenantIsolationService $isolation) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('enterprise_hardening.tenant_isolation', true)) {
            return $next($request);
        }

        $mission = $request->route('mission');

        if ($mission instanceof Mission) {
            $this->isolation->assertMissionAccess($mission, $request->user());
        }

        return $next($request);
    }
}
