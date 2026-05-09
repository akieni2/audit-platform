<?php

namespace App\Policies;

use App\Models\User;

/**
 * Administration des comptes : moindre privilège (OWASP).
 */
class UserPolicy
{
    private function canManageAccounts(User $actor): bool
    {
        if ($actor->role === 'admin') {
            return true;
        }

        return $actor->institutionalRole?->slug === 'super_admin'
            || $actor->hasPermission('manage_users');
    }

    public function viewAny(User $actor): bool
    {
        return $this->canManageAccounts($actor);
    }

    public function view(User $actor, User $model): bool
    {
        return $this->canManageAccounts($actor);
    }

    public function create(User $actor): bool
    {
        return $this->canManageAccounts($actor);
    }

    public function update(User $actor, User $model): bool
    {
        return $this->canManageAccounts($actor);
    }

    public function resetPassword(User $actor, User $model): bool
    {
        return $this->canManageAccounts($actor);
    }

    /** Suppression du compte courant (profil) — interdit pour le Super Administrateur système. */
    public function delete(User $actor, User $model): bool
    {
        return $actor->id === $model->id
            && ! $model->isProtectedSystemAdministrator();
    }
}
