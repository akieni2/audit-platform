<?php

namespace App\Services\Runtime;

use App\Models\RuntimeMetric;
use Illuminate\Support\Facades\Schema;

final class RuntimeMetricsService
{
    /**
     * @param  array<string, scalar|null>  $dimensions
     */
    public function increment(
        string $metricKey,
        int|float $delta = 1,
        array $dimensions = [],
        ?string $scopeType = null,
        int|string|null $scopeId = null,
    ): RuntimeMetric {
        return $this->adjust(
            metricKey: $metricKey,
            delta: $delta,
            dimensions: $dimensions,
            scopeType: $scopeType,
            scopeId: $scopeId,
            metricType: 'counter',
        );
    }

    /**
     * @param  array<string, scalar|null>  $dimensions
     */
    public function gauge(
        string $metricKey,
        int|float $value,
        array $dimensions = [],
        ?string $scopeType = null,
        int|string|null $scopeId = null,
    ): RuntimeMetric {
        $normalizedDimensions = $this->normalizeDimensions($dimensions);
        if (! Schema::hasTable('runtime_metrics')) {
            return new RuntimeMetric([
                'metric_key' => $metricKey,
                'metric_type' => 'gauge',
                'scope_type' => $scopeType,
                'scope_id' => $scopeId !== null ? (string) $scopeId : null,
                'dimensions_hash' => $this->hashDimensions($normalizedDimensions),
                'dimensions' => $normalizedDimensions,
                'value' => $value,
                'recorded_at' => now(),
            ]);
        }

        $metric = RuntimeMetric::query()->firstOrNew([
            'metric_key' => $metricKey,
            'metric_type' => 'gauge',
            'scope_type' => $scopeType,
            'scope_id' => $scopeId !== null ? (string) $scopeId : null,
            'dimensions_hash' => $this->hashDimensions($normalizedDimensions),
        ]);

        $metric->fill([
            'dimensions' => $normalizedDimensions,
            'value' => $value,
            'recorded_at' => now(),
        ]);
        $metric->save();

        return $metric;
    }

    /**
     * @param  array<string, scalar|null>  $dimensions
     */
    private function adjust(
        string $metricKey,
        int|float $delta,
        array $dimensions,
        ?string $scopeType,
        int|string|null $scopeId,
        string $metricType,
    ): RuntimeMetric {
        $normalizedDimensions = $this->normalizeDimensions($dimensions);
        if (! Schema::hasTable('runtime_metrics')) {
            return new RuntimeMetric([
                'metric_key' => $metricKey,
                'metric_type' => $metricType,
                'scope_type' => $scopeType,
                'scope_id' => $scopeId !== null ? (string) $scopeId : null,
                'dimensions_hash' => $this->hashDimensions($normalizedDimensions),
                'dimensions' => $normalizedDimensions,
                'value' => $delta,
                'recorded_at' => now(),
            ]);
        }

        $metric = RuntimeMetric::query()->firstOrNew([
            'metric_key' => $metricKey,
            'metric_type' => $metricType,
            'scope_type' => $scopeType,
            'scope_id' => $scopeId !== null ? (string) $scopeId : null,
            'dimensions_hash' => $this->hashDimensions($normalizedDimensions),
        ]);

        $metric->dimensions = $normalizedDimensions;
        $metric->value = ((float) ($metric->value ?? 0)) + $delta;
        $metric->recorded_at = now();
        $metric->save();

        return $metric;
    }

    /**
     * @param  array<string, scalar|null>  $dimensions
     * @return array<string, scalar|null>
     */
    private function normalizeDimensions(array $dimensions): array
    {
        ksort($dimensions);

        return $dimensions;
    }

    /**
     * @param  array<string, scalar|null>  $dimensions
     */
    private function hashDimensions(array $dimensions): string
    {
        return sha1(json_encode($dimensions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');
    }
}
