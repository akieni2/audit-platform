<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class OrganizationalRoleCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['slug' => 'directeur', 'name' => 'Directeur', 'hierarchy_level' => 90, 'permissions' => ['view', 'create', 'update', 'validate', 'supervise', 'view_department_data', 'create_mission', 'update_mission', 'validate_mission', 'supervise_department', 'access_copri_menu']],
            ['slug' => 'directeur_adjoint', 'name' => 'Directeur adjoint', 'hierarchy_level' => 75, 'permissions' => ['view', 'create', 'update', 'supervise', 'view_department_data', 'create_mission', 'update_mission', 'supervise_department', 'access_copri_menu']],
            ['slug' => 'chef_service', 'name' => 'Chef de service', 'hierarchy_level' => 55, 'permissions' => ['view', 'create', 'update', 'view_department_data', 'supervise_department', 'access_copri_menu']],
            ['slug' => 'agent_operationnel', 'name' => 'Agent opérationnel', 'hierarchy_level' => 15, 'permissions' => ['view', 'view_department_data']],
        ];

        foreach ($roles as $definition) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'hierarchy_level' => $definition['hierarchy_level'],
                    'active' => true,
                ]
            );

            $role->permissions()->sync(
                Permission::query()->whereIn('slug', $definition['permissions'])->pluck('id')
            );
        }
    }
}
