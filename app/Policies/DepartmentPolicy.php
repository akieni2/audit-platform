<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

/**
 * Structure organisationnelle (pôles / départements) — Gate « manageDepartments ».
 */
class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessOrganizationChart();
    }

    public function view(User $user, Department $department): bool
    {
        if ($user->canViewGlobalOrganization()) {
            return true;
        }

        $root = $user->department;

        return $root !== null && ((int) $department->id === (int) $root->id || $department->isDescendantOf($root));
    }

    public function create(User $user): bool
    {
        return $user->canAdministerOrganization();
    }

    public function update(User $user, Department $department): bool
    {
        return $user->canAdministerOrganization() || $user->isDepartmentSupervisorOf($department->id);
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->canAdministerOrganization();
    }
}
