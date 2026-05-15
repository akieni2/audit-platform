<?php

namespace App\Services\Ai\Observability;

use App\Models\AiExecutionLog;
use Illuminate\Support\Facades\Schema;

class AiMonitoringService
{
    public function snapshot(): array
    {
        if (! Schema::hasTable('ai_execution_logs')) {
            return ['executions' => 0, 'failed' => 0, 'avg_latency_ms' => 0];
        }

        $total = AiExecutionLog::query()->count();
        $failed = AiExecutionLog::query()->where('status', 'failed')->count();
        $avgLatency = (int) AiExecutionLog::query()->avg('latency_ms');

        return [
            'executions' => $total,
            'failed' => $failed,
            'avg_latency_ms' => $avgLatency,
            'healthy' => $failed < max(5, (int) ($total * 0.1)),
        ];
    }
}
