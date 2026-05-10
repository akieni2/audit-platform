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
        return $user->canManageDepartments();
    }

    public function view(User $user, Department $department): bool
    {
        return $user->canManageDepartments();
    }

    public function create(User $user): bool
    {
        return $user->canManageDepartments();
    }

    public function update(User $user, Department $department): bool
    {
        return $user->canManageDepartments();
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->canManageDepartments();
    }
}
