<?php

namespace App\Services\Swot;

use App\Models\SwotAuditLog;
use App\Models\SwotAnalysis;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class SwotAuditService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function log(string $eventName, ?SwotAnalysis $analysis = null, ?User $actor = null, ?string $status = null, array $payload = []): ?SwotAuditLog
    {
        if (! Schema::hasTable('swot_audit_logs')) {
            return null;
        }

        return SwotAuditLog::query()->create([
            'swot_template_id' => $analysis?->swot_template_id,
            'swot_analysis_id' => $analysis?->id,
            'mission_id' => $analysis?->mission_id,
            'department_id' => $analysis?->department_id,
            'workflow_instance_id' => $analysis?->workflow_instance_id,
            'actor_id' => $actor?->id,
            'event_name' => $eventName,
            'status' => $status,
            'payload' => $payload,
            'occurred_at' => now(),
        ]);
    }
}
