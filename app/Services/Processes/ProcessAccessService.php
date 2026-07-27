<?php

namespace App\Services\Processes;

use App\Models\Department;
use App\Models\ProcessModuleAccess;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class ProcessAccessService
{
    public function role(User $user): ?string
    {
        if ($user->isInstitutionalSuperAdmin()) {
            return 'administrator';
        }
        if (! Schema::hasTable('process_module_accesses')) {
            return null;
        }
        if ($user->department_id === null) {
            return null;
        }
        $ancestry = Department::ancestryIds((int) $user->department_id);
        $grants = ProcessModuleAccess::query()->whereIn('department_id', $ancestry)->get()->keyBy('department_id');
        foreach ($ancestry as $index => $departmentId) {
            $grant = $grants->get($departmentId);
            if (! $grant) {
                continue;
            }
            if ($index === 0 || $grant->inherit_to_children) {
                return $grant->active ? $grant->default_role : null;
            }
        }

        return null;
    }

    public function allows(User $user, string $minimum = 'viewer'): bool
    {
        $role = $this->role($user);

        return $role !== null && ProcessModuleAccess::roleRank($role) >= ProcessModuleAccess::roleRank($minimum);
    }
}
