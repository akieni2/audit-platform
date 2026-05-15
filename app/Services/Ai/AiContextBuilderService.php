<?php

namespace App\Services\Ai;

use App\Domain\Ai\Enums\AiContextType;
use App\Models\Mission;
use App\Models\User;
use App\Services\Tenant\TenantIsolationService;

class AiContextBuilderService
{
    public function __construct(private TenantIsolationService $tenants) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Mission $mission, User $user, AiContextType $type, array $extra = []): array
    {
        $tenant = $this->tenants->current($user);

        return array_merge([
            'context_type' => $type->value,
            'mission_id' => $mission->id,
            'mission_organisation' => $mission->organisation,
            'mission_status' => $mission->mission_status,
            'department_id' => $mission->department_id,
            'tenant_key' => $tenant->tenantKey(),
            'tenant_scope' => $tenant->scope,
            'assistive_only' => true,
            'requires_human_validation' => true,
        ], $extra);
    }
}
