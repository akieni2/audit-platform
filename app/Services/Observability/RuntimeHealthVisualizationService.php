<?php

namespace App\Services\Observability;

use Illuminate\Support\Collection;

class RuntimeHealthVisualizationService
{
    /**
     * @param  Collection<int, mixed>  $businessEvents
     * @param  Collection<int, mixed>  $runtimeMetrics
     * @param  Collection<int, mixed>  $workflowTemplates
     * @return array<string, mixed>
     */
    public function build(Collection $businessEvents, Collection $runtimeMetrics, Collection $workflowTemplates): array
    {
        return [
            'events' => $businessEvents->count(),
            'metrics' => $runtimeMetrics->count(),
            'templates' => $workflowTemplates->count(),
            'active_templates' => $workflowTemplates->where('active', true)->count(),
            'latest_event_at' => optional($businessEvents->first())->occurred_at?->format('d/m/Y H:i'),
        ];
    }
}
