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
        return $actor->canAccessAdministrationMenu() || $actor->canManageDepartmentUsers();
    }

    public function viewAny(User $actor): bool
    {
        return $this->canManageAccounts($actor);
    }

    public function view(User $actor, User $model): bool
    {
        if ($actor->canSuperviseAllDepartments()) {
            return true;
        }

        return $this->canManageAccounts($actor)
            && in_array((int) $model->department_id, $actor->managedDepartmentIds(), true);
    }

    public function create(User $actor): bool
    {
        return $this->canManageAccounts($actor);
    }

    public function update(User $actor, User $model): bool
    {
        return $this->view($actor, $model);
    }

    public function resetPassword(User $actor, User $model): bool
    {
        return $this->view($actor, $model);
    }

    /** Suppression du compte courant (profil) — interdit pour le Super Administrateur système. */
    public function delete(User $actor, User $model): bool
    {
        return $actor->id === $model->id
            && ! $model->isProtectedSystemAdministrator();
    }

    /**
     * Suppression IAM (soft delete) — réservée aux super administrateurs institutionnels.
     */
    public function deleteFromAdministration(User $actor, User $model): bool
    {
        return $actor->isInstitutionalSuperAdmin();
    }
}
