<?php

namespace App\Services\Audit;

use App\Models\DataAccessEvent;
use App\Models\User;
use App\Services\Tenant\TenantIsolationService;
use Illuminate\Support\Facades\Schema;

class DataAccessAuditService
{
    public function __construct(private TenantIsolationService $tenants) {}

    public function record(
        User $user,
        string $accessType,
        string $resourceType,
        ?int $resourceId,
        string $outcome = 'allowed',
        array $metadata = [],
    ): ?DataAccessEvent {
        if (! Schema::hasTable('data_access_events')) {
            return null;
        }

        $context = $this->tenants->current($user);

        return DataAccessEvent::query()->create([
            'tenant_context_id' => $context->tenant?->id,
            'user_id' => $user->id,
            'access_type' => $accessType,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'outcome' => $outcome,
            'metadata' => $metadata,
            'accessed_at' => now(),
        ]);
    }
}
