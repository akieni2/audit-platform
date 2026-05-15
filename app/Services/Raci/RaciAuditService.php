<?php

namespace App\Services\Raci;

use App\Models\RaciAssignment;
use App\Models\RaciAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class RaciAuditService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function log(string $eventName, ?RaciAssignment $assignment = null, ?User $actor = null, ?string $status = null, array $payload = []): ?RaciAuditLog
    {
        if (! Schema::hasTable('raci_audit_logs')) {
            return null;
        }

        return RaciAuditLog::query()->create([
            'raci_template_id' => $assignment?->raci_template_id,
            'raci_matrix_id' => $assignment?->raci_matrix_id,
            'raci_assignment_id' => $assignment?->id,
            'mission_id' => $assignment?->mission_id,
            'department_id' => $assignment?->department_id,
            'workflow_instance_id' => $assignment?->raciMatrix?->workflow_instance_id,
            'actor_id' => $actor?->id,
            'event_name' => $eventName,
            'status' => $status,
            'payload' => $payload,
            'occurred_at' => now(),
        ]);
    }
}
