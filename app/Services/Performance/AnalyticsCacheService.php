<?php

namespace App\Services\Performance;

use App\Services\Tenant\TenantIsolationService;
use Illuminate\Support\Facades\Cache;

class AnalyticsCacheService
{
    public function __construct(private TenantIsolationService $tenants) {}

    public function remember(string $metric, callable $callback): mixed
    {
        $key = $this->tenants->current()->cachePrefix().':analytics:'.$metric;
        $ttl = (int) config('enterprise_hardening.analytics_cache_ttl', 600);

        return Cache::remember($key, $ttl, $callback);
    }
}
