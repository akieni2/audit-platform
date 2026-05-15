<?php

namespace App\Support\Tenant;

use App\Models\TenantContext;

class ResolvedTenantContext
{
    public function __construct(
        public readonly ?TenantContext $tenant,
        public readonly ?int $departmentId,
        public readonly string $scope,
        public readonly bool $nationalScope,
    ) {}

    public function tenantKey(): ?string
    {
        return $this->tenant?->tenant_key;
    }

    public function cachePrefix(): string
    {
        if ($this->nationalScope) {
            return 'national';
        }

        return $this->tenant?->cache_prefix ?? ('dept_'.$this->departmentId);
    }

    public function isStrictIsolation(): bool
    {
        return $this->tenant?->isolation_mode === 'strict';
    }
}
