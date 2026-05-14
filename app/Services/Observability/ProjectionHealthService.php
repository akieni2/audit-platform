<?php

namespace App\Services\Observability;

use Illuminate\Support\Collection;

class ProjectionHealthService
{
    /**
     * @param  Collection<int, mixed>  $integrityChecks
     * @return array<string, mixed>
     */
    public function snapshot(Collection $integrityChecks): array
    {
        return [
            'ok' => $integrityChecks->where('status', 'ok')->count(),
            'warning' => $integrityChecks->where('status', 'warning')->count(),
            'failed' => $integrityChecks->where('status', 'failed')->count(),
            'latest' => optional($integrityChecks->first())->checked_at?->format('d/m/Y H:i'),
        ];
    }
}
