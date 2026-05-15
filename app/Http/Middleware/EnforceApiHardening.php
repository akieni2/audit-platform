<?php

namespace App\Http\Middleware;

use App\Services\Api\ApiRateLimitService;
use App\Services\Api\ApiSecurityService;
use App\Services\Api\ApiSignatureService;
use App\Services\Tenant\TenantIsolationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceApiHardening
{
    public function __construct(
        private TenantIsolationService $tenants,
        private ApiSecurityService $security,
        private ApiRateLimitService $rateLimit,
        private ApiSignatureService $signatures,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        $context = $this->tenants->current($user);
        abort_unless($this->security->authorizeRequest($user, $context, $request), 403);

        if ($this->rateLimit->tooManyAttempts($request, $context)) {
            abort(429, 'API rate limit exceeded.');
        }

        $this->rateLimit->hit($request, $context);

        if (! $this->signatures->verify($request)) {
            abort(403, 'Invalid API signature.');
        }

        return $next($request);
    }
}
