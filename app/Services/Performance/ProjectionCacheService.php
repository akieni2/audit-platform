<?php

namespace App\Services\Performance;

use Illuminate\Support\Facades\Cache;

class ProjectionCacheService
{
    public function remember(int $missionId, callable $callback): mixed
    {
        return Cache::remember(
            'projection:mission:'.$missionId,
            (int) config('enterprise_hardening.runtime_cache_ttl', 300),
            $callback,
        );
    }
}
