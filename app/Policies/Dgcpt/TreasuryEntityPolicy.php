<?php

namespace App\Policies\Dgcpt;

use App\Models\Dgcpt\TreasuryEntity;
use App\Models\User;

class TreasuryEntityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->approval_status === User::APPROVAL_STATUS_APPROVED && $user->active;
    }

    public function view(User $user, TreasuryEntity $entity): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($user->canViewAllInstitutionalData()) {
            return true;
        }

        return TreasuryEntity::query()
            ->whereKey($entity->id)
            ->visibleToUser($user)
            ->exists();
    }

    public function manage(User $user): bool
    {
        return $user->canManageDepartments();
    }
}
