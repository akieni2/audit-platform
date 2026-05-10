<?php

namespace App\Policies;

use App\Domain\Risk\Enums\CriticalityLevel;
use App\Models\Risque;
use App\Models\User;

/**
 * RBAC : modification des risques critiques réservée aux Admins et Risk Managers (OWASP — moindre privilège).
 */
class RisquePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Risque $risque): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Risque $risque): bool
    {
        if ($this->isCritical($risque)) {
            return $user->canManageRisks();
        }

        return true;
    }

    public function delete(User $user, Risque $risque): bool
    {
        return $this->update($user, $risque);
    }

    private function isCritical(Risque $risque): bool
    {
        return $risque->criticite_inherent === CriticalityLevel::Critique->value
            || $risque->criticite_residuel === CriticalityLevel::Critique->value;
    }
}
