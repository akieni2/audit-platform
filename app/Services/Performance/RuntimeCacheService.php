<?php

namespace App\Services\Performance;

use App\Services\Tenant\TenantIsolationService;
use Illuminate\Support\Facades\Cache;

class RuntimeCacheService
{
    public function __construct(private TenantIsolationService $tenants) {}

    public function remember(string $suffix, callable $callback): mixed
    {
        $key = $this->tenants->current()->cachePrefix().':runtime:'.$suffix;
        $ttl = (int) config('enterprise_hardening.runtime_cache_ttl', 300);

        return Cache::remember($key, $ttl, $callback);
    }

    public function forget(string $suffix): void
    {
        Cache::forget($this->tenants->current()->cachePrefix().':runtime:'.$suffix);
    }
}
