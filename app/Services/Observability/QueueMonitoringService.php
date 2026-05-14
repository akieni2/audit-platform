<?php

namespace App\Services\Observability;

use Illuminate\Support\Collection;

class QueueMonitoringService
{
    /**
     * @param  Collection<int, mixed>  $workflowTemplates
     * @return array<string, mixed>
     */
    public function snapshot(Collection $workflowTemplates): array
    {
        return [
            'ready' => $workflowTemplates->where('status', 'published')->count(),
            'draft' => $workflowTemplates->where('status', 'draft')->count(),
            'archived' => $workflowTemplates->where('status', 'archived')->count(),
            'throughput' => $workflowTemplates->sum('instances_count'),
        ];
    }
}
