<?php

namespace App\Services\Observability;

use App\Services\Performance\AnalyticsCacheService;

class AnalyticsMonitoringService
{
    public function __construct(private AnalyticsCacheService $cache) {}

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return [
            'cache_ttl' => (int) config('enterprise_hardening.analytics_cache_ttl', 600),
            'warmed' => true,
        ];
    }
}
