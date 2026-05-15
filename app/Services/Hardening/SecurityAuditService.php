<?php

namespace App\Services\Hardening;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Audit\ImmutableAuditTrailService;
use App\Services\Iam\SecurityAuditService as IamSecurityAuditService;
use App\Services\Tenant\TenantIsolationService;
use Illuminate\Http\Request;

class SecurityAuditService
{
    public function __construct(
        private IamSecurityAuditService $iamAudit,
        private ImmutableAuditTrailService $immutableTrail,
        private TenantIsolationService $tenants,
    ) {}

    public function log(
        string $action,
        string $module,
        ?string $description,
        ?User $user,
        Request $request,
        ?array $metadata = null,
    ): AuditLog {
        $log = $this->iamAudit->log($action, $module, $description, $user, $request, $metadata);

        $this->immutableTrail->record(
            eventType: $action,
            module: $module,
            description: $description,
            user: $user,
            request: $request,
            payload: $metadata,
            resourceType: null,
            resourceId: null,
        );

        return $log;
    }

    public function runtimeActionSigned(
        User $user,
        Request $request,
        string $action,
        array $context = [],
    ): string {
        $tenant = $this->tenants->current($user);

        $payload = array_merge($context, [
            'user_id' => $user->id,
            'tenant' => $tenant->tenantKey(),
            'action' => $action,
            'ip' => $request->ip(),
            'at' => now()->toIso8601String(),
        ]);

        return hash_hmac('sha256', json_encode($payload), (string) config('enterprise_hardening.signing_key'));
    }
}
