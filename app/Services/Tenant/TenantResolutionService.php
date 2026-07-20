<?php

namespace App\Services\Tenant;

use App\Models\Department;
use App\Models\TenantAuditScope;
use App\Models\TenantContext;
use App\Models\TenantSecurityPolicy;
use App\Models\User;
use App\Support\Tenant\ResolvedTenantContext;
use Illuminate\Support\Facades\Schema;

class TenantResolutionService
{
    public function resolveForUser(?User $user): ResolvedTenantContext
    {
        if ($user === null) {
            return new ResolvedTenantContext(null, null, 'anonymous', false);
        }

        $user->loadMissing('institutionalRole');

        if ($user->canViewAllInstitutionalData()) {
            return new ResolvedTenantContext(null, null, 'national', true);
        }

        $departmentId = $user->department_id;

        if ($departmentId === null) {
            return new ResolvedTenantContext(null, null, 'unscoped', false);
        }

        $tenant = $this->ensureTenantForDepartment($departmentId);

        return new ResolvedTenantContext($tenant, $departmentId, 'department', false);
    }

    public function ensureTenantForDepartment(int $departmentId): ?TenantContext
    {
        if (! Schema::hasTable('tenant_contexts')) {
            return null;
        }

        $existing = TenantContext::query()
            ->where('department_id', $departmentId)
            ->where('active', true)
            ->first();

        if ($existing !== null) {
            return $existing->loadMissing('securityPolicy');
        }

        $department = Department::query()->find($departmentId);

        if ($department === null) {
            return null;
        }

        $tenant = TenantContext::query()->create([
            'department_id' => $department->id,
            'tenant_key' => strtolower($department->code ?? 'dept_'.$department->id),
            'isolation_mode' => 'strict',
            'cache_prefix' => 'tenant_'.strtolower($department->code ?? $department->id),
            'active' => true,
        ]);

        $this->seedDefaults($tenant);

        return $tenant->fresh(['securityPolicy', 'auditScopes']);
    }

    private function seedDefaults(TenantContext $tenant): void
    {
        if (Schema::hasTable('tenant_security_policies') && $tenant->securityPolicy === null) {
            TenantSecurityPolicy::query()->create([
                'tenant_context_id' => $tenant->id,
                'mfa_required' => false,
                'strict_session_binding' => true,
                'max_session_minutes' => (int) config('session.lifetime', 120),
                'signed_actions_required' => (bool) config('enterprise_hardening.signed_runtime_actions', true),
                'api_access_enabled' => true,
            ]);
        }

        if (! Schema::hasTable('tenant_audit_scopes')) {
            return;
        }

        foreach (['workflows', 'forms', 'questionnaires', 'missions', 'risks', 'controls', 'swot', 'raci', 'analytics'] as $module) {
            TenantAuditScope::query()->firstOrCreate(
                ['tenant_context_id' => $tenant->id, 'module' => $module],
                ['immutable_trail_enabled' => true, 'retention_days' => 2555],
            );
        }
    }
}
