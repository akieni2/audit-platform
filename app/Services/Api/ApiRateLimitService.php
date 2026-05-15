<?php

namespace App\Services\Api;

use App\Support\Tenant\ResolvedTenantContext;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;

class ApiRateLimitService
{
    public function __construct(private RateLimiter $limiter) {}

    public function tooManyAttempts(Request $request, ResolvedTenantContext $context): bool
    {
        $key = ($context->tenantKey() ?? 'national').'|'.$request->user()?->id.'|'.$request->ip();

        return $this->limiter->tooManyAttempts($key, 120);
    }

    public function hit(Request $request, ResolvedTenantContext $context): void
    {
        $key = ($context->tenantKey() ?? 'national').'|'.$request->user()?->id.'|'.$request->ip();
        $this->limiter->hit($key, 60);
    }
}
