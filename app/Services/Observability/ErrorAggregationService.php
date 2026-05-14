<?php

namespace App\Services\Observability;

use Illuminate\Support\Collection;

class ErrorAggregationService
{
    /**
     * @param  Collection<int, mixed>  $businessEvents
     * @param  Collection<int, mixed>  $runtimeMetrics
     * @param  Collection<int, mixed>  $integrityChecks
     * @return array<string, mixed>
     */
    public function summarize(Collection $businessEvents, Collection $runtimeMetrics, Collection $integrityChecks): array
    {
        $errors = collect();

        foreach ($businessEvents->filter(fn ($event) => in_array((string) data_get($event, 'status'), ['failed', 'error'], true)) as $event) {
            $errors->push([
                'title' => (string) data_get($event, 'event_name', 'Business event'),
                'message' => 'Échec métier détecté.',
                'source' => 'business_event',
            ]);
        }

        foreach ($runtimeMetrics->filter(fn ($metric) => str_contains(strtolower((string) data_get($metric, 'metric_key', '')), 'error')) as $metric) {
            $errors->push([
                'title' => (string) data_get($metric, 'metric_key', 'Runtime metric'),
                'message' => 'Métrique runtime anormale: '.data_get($metric, 'value'),
                'source' => 'runtime_metric',
            ]);
        }

        foreach ($integrityChecks->filter(fn ($check) => (string) data_get($check, 'status') !== 'ok') as $check) {
            $errors->push([
                'title' => (string) data_get($check, 'projection_type', 'Projection'),
                'message' => 'Mismatch(s): '.data_get($check, 'mismatch_count', 0),
                'source' => 'projection_integrity',
            ]);
        }

        return [
            'count' => $errors->count(),
            'items' => $errors->take(12)->values()->all(),
        ];
    }
}
