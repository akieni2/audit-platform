<?php

namespace Tests\Feature\Concerns;

use App\Models\Department;
use App\Models\Role;
use App\Models\TenantContext;
use App\Models\User;
use App\Services\Tenant\TenantResolutionService;

trait BuildsEnterpriseHardeningContext
{
    use BuildsWorkflowRuntimeContext;

    private function hardeningDepartment(string $code = 'HRD'): Department
    {
        return $this->createDepartment($code);
    }

    private function hardeningAdminUser(?Department $department = null): User
    {
        $department ??= $this->hardeningDepartment('ADM');

        return $this->createUser('administrateur_institutionnel', $department, 900);
    }

    private function hardeningInspectorUser(Department $department): User
    {
        return $this->createUser('inspecteur_services', $department, 100);
    }

    private function hardeningTenant(Department $department): TenantContext
    {
        return app(TenantResolutionService::class)->ensureTenantForDepartment($department->id);
    }
}
