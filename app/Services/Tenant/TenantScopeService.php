<?php

namespace App\Services\Tenant;

use App\Models\Mission;
use App\Support\Tenant\ResolvedTenantContext;
use Illuminate\Database\Eloquent\Builder;

class TenantScopeService
{
    public function applyMissionScope(Builder $query, ResolvedTenantContext $context): Builder
    {
        if ($context->nationalScope || $context->departmentId === null) {
            return $query;
        }

        return $query->where(function (Builder $scoped) use ($context): void {
            $scoped->where('department_id', $context->departmentId)
                ->orWhere('supervising_department_id', $context->departmentId);
        });
    }

    public function canAccessMission(ResolvedTenantContext $context, Mission $mission): bool
    {
        if ($context->nationalScope) {
            return true;
        }

        if ($context->departmentId === null) {
            return false;
        }

        return (int) $mission->department_id === $context->departmentId
            || (int) $mission->supervising_department_id === $context->departmentId;
    }

    public function cacheKey(ResolvedTenantContext $context, string $suffix): string
    {
        return $context->cachePrefix().':'.$suffix;
    }
}
