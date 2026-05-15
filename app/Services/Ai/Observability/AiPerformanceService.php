<?php

namespace App\Services\Ai\Observability;

use App\Models\AiExecutionLog;
use Illuminate\Support\Facades\Schema;

class AiPerformanceService
{
    public function driversBreakdown(): array
    {
        if (! Schema::hasTable('ai_execution_logs')) {
            return [];
        }

        return AiExecutionLog::query()
            ->selectRaw('driver, count(*) as total, avg(latency_ms) as avg_latency')
            ->groupBy('driver')
            ->get()
            ->map(fn ($row) => [
                'driver' => $row->driver,
                'total' => (int) $row->total,
                'avg_latency_ms' => (int) $row->avg_latency,
            ])
            ->all();
    }
}
