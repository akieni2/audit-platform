<?php

namespace App\Services\Audit;

use App\Models\ImmutableAuditEvent;
use App\Models\User;
use App\Services\Tenant\TenantIsolationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ImmutableAuditTrailService
{
    public function __construct(private TenantIsolationService $tenants) {}

    public function record(
        string $eventType,
        string $module,
        ?string $description,
        ?User $user,
        ?Request $request = null,
        ?array $payload = null,
        ?string $resourceType = null,
        ?int $resourceId = null,
        ?string $actionSignature = null,
    ): ?ImmutableAuditEvent {
        if (! config('enterprise_hardening.immutable_audit', true)) {
            return null;
        }

        if (! Schema::hasTable('immutable_audit_events')) {
            return null;
        }

        $context = $this->tenants->current($user);
        $previous = ImmutableAuditEvent::query()->latest('id')->value('integrity_hash');
        $occurredAt = now();

        $body = [
            'event_type' => $eventType,
            'module' => $module,
            'user_id' => $user?->id,
            'tenant_context_id' => $context->tenant?->id,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'payload' => $payload,
            'occurred_at' => $occurredAt->toIso8601String(),
            'previous_hash' => $previous,
        ];

        $integrityHash = hash('sha256', json_encode($body));

        return ImmutableAuditEvent::query()->create([
            'tenant_context_id' => $context->tenant?->id,
            'user_id' => $user?->id,
            'event_type' => $eventType,
            'module' => $module,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'action_signature' => $actionSignature,
            'integrity_hash' => $integrityHash,
            'previous_hash' => $previous,
            'description' => $description,
            'payload' => $payload,
            'ip' => $request?->ip(),
            'occurred_at' => $occurredAt,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, ImmutableAuditEvent>
     */
    public function timeline(?int $tenantContextId = null, int $limit = 50)
    {
        $query = ImmutableAuditEvent::query()->latest('occurred_at')->limit($limit);

        if ($tenantContextId !== null) {
            $query->where('tenant_context_id', $tenantContextId);
        }

        return $query->with('actor')->get();
    }
}
