<?php

namespace App\Services\Observability;

use Illuminate\Support\Collection;

class ProjectionMonitoringService
{
    public function __construct(private ProjectionHealthService $health) {}

    /**
     * @param  Collection<int, mixed>  $checks
     * @return array<string, mixed>
     */
    public function monitor(Collection $checks): array
    {
        $base = $this->health->snapshot($checks);

        return array_merge($base, [
            'monitored_at' => now()->toIso8601String(),
            'total_checks' => $checks->count(),
        ]);
    }
}
