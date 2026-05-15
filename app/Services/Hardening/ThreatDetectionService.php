<?php

namespace App\Services\Hardening;

use App\Models\RuntimeSecurityEvent;
use App\Models\User;
use App\Services\Tenant\TenantIsolationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ThreatDetectionService
{
    public function __construct(private TenantIsolationService $tenants) {}

    public function recordSuspicious(
        string $eventType,
        ?User $user,
        Request $request,
        string $severity = 'warning',
        bool $blocked = false,
        array $payload = [],
    ): ?RuntimeSecurityEvent {
        if (! Schema::hasTable('runtime_security_events')) {
            return null;
        }

        $context = $this->tenants->current($user);

        return RuntimeSecurityEvent::query()->create([
            'tenant_context_id' => $context->tenant?->id,
            'user_id' => $user?->id,
            'mission_id' => $payload['mission_id'] ?? null,
            'severity' => $severity,
            'event_type' => $eventType,
            'threat_level' => $payload['threat_level'] ?? 'medium',
            'blocked' => $blocked,
            'payload' => array_merge($payload, [
                'ip' => $request->ip(),
                'route' => $request->route()?->getName(),
            ]),
            'occurred_at' => now(),
        ]);
    }

    public function detectRoleEscalation(?User $user, string $attemptedAbility): bool
    {
        if ($user === null) {
            return true;
        }

        return ! $user->can($attemptedAbility);
    }
}
