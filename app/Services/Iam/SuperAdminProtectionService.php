<?php

namespace App\Services\Iam;

use App\Models\Role;
use App\Models\User;

/**
 * Règles métier : protection du compte super administrateur et du dernier administrateur système.
 */
class SuperAdminProtectionService
{
    public function isProtectedSystemEmail(User $user): bool
    {
        $configured = strtolower((string) config('dgcpt.super_admin_email', 'admin@dgcpt.ga'));

        return strtolower((string) $user->email) === $configured;
    }

    public function institutionalSuperAdminRoleId(): ?int
    {
        return Role::query()->where('slug', 'super_admin')->value('id');
    }

    public function superAdministratorCount(): int
    {
        $roleId = $this->institutionalSuperAdminRoleId();
        if ($roleId === null) {
            return 0;
        }

        return User::query()->where('role_id', $roleId)->count();
    }

    public function mayDeactivate(User $target): bool
    {
        if ($this->isProtectedSystemEmail($target)) {
            return false;
        }

        if ($target->institutionalRole?->slug === 'super_admin'
            && $this->superAdministratorCount() <= 1) {
            return false;
        }

        return true;
    }

    public function mayRemoveSuperAdminRole(User $target): bool
    {
        if ($this->isProtectedSystemEmail($target)) {
            return false;
        }

        if ($target->institutionalRole?->slug !== 'super_admin') {
            return true;
        }

        return $this->superAdministratorCount() > 1;
    }

    public function mayDelete(User $target): bool
    {
        if ($this->isProtectedSystemEmail($target)) {
            return false;
        }

        if ($target->institutionalRole?->slug === 'super_admin') {
            return $this->superAdministratorCount() > 1;
        }

        return true;
    }
}
