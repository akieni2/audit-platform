<?php

namespace App\Services\Assets;

use App\Models\AssetModuleAccess;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class AssetAccessService
{
    public function role(User $u): ?string
    {
        if ($u->isInstitutionalSuperAdmin()) {
            return 'administrator';
        }if (! Schema::hasTable('asset_module_accesses') || $u->department_id === null) {
            return null;
        }$ancestry = Department::ancestryIds((int) $u->department_id);
        $grants = AssetModuleAccess::whereIn('department_id', $ancestry)->get()->keyBy('department_id');
        foreach ($ancestry as $i => $id) {
            $g = $grants->get($id);
            if (! $g) {
                continue;
            }if ($i === 0 || $g->inherit_to_children) {
                return $g->active ? $g->default_role : null;
            }
        }

return null;
    }

    public function allows(User $u, string $minimum = 'viewer'): bool
    {
        $role = $this->role($u);

        return $role !== null && AssetModuleAccess::roleRank($role) >= AssetModuleAccess::roleRank($minimum);
    }
}
