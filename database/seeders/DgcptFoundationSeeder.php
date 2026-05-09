<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DgcptFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Pôle Contrôle des postes comptables', 'code' => 'COMPTA', 'type' => 'pole', 'description' => 'Contrôle et audit des postes comptables.'],
            ['name' => 'Pôle Audit et Maîtrise des Risques', 'code' => 'RISQUES', 'type' => 'pole', 'description' => 'Audit interne, contrôle interne, cartographie des risques.'],
            ['name' => 'Pôle Management et Pilotage', 'code' => 'PILOTAGE', 'type' => 'pole', 'description' => 'Pilotage stratégique et indicateurs.'],
            ['name' => 'Pôle Informatique et Risques SI', 'code' => 'IT', 'type' => 'pole', 'description' => 'Audit SI, cybersécurité, risques SI.'],
        ];

        foreach ($departments as $row) {
            Department::query()->updateOrCreate(
                ['code' => $row['code']],
                $row + ['active' => true]
            );
        }

        $permissionSlugs = [
            ['slug' => 'view', 'name' => 'Consulter', 'group' => 'core'],
            ['slug' => 'create', 'name' => 'Créer', 'group' => 'core'],
            ['slug' => 'update', 'name' => 'Modifier', 'group' => 'core'],
            ['slug' => 'delete', 'name' => 'Supprimer', 'group' => 'core'],
            ['slug' => 'validate', 'name' => 'Valider', 'group' => 'workflow'],
            ['slug' => 'escalate', 'name' => 'Escalader', 'group' => 'workflow'],
            ['slug' => 'supervise', 'name' => 'Superviser', 'group' => 'executive'],
            ['slug' => 'export', 'name' => 'Exporter', 'group' => 'reporting'],
        ];

        foreach ($permissionSlugs as $p) {
            Permission::query()->updateOrCreate(
                ['slug' => $p['slug']],
                ['name' => $p['name'], 'group' => $p['group']]
            );
        }

        $roles = [
            ['slug' => 'inspecteur_services', 'name' => 'Inspecteur des Services', 'hierarchy_level' => 100],
            ['slug' => 'inspecteur_adjoint', 'name' => 'Inspecteur adjoint', 'hierarchy_level' => 80],
            ['slug' => 'inspecteur_verificateur', 'name' => 'Inspecteur vérificateur', 'hierarchy_level' => 60],
            ['slug' => 'inspecteur_verificateur_adjoint', 'name' => 'Inspecteur vérificateur adjoint', 'hierarchy_level' => 40],
            ['slug' => 'charge_verification', 'name' => 'Chargé de vérification', 'hierarchy_level' => 20],
            ['slug' => 'admin', 'name' => 'Administrateur technique', 'hierarchy_level' => 90],
            ['slug' => 'risk_manager', 'name' => 'Risk Manager', 'hierarchy_level' => 70],
            ['slug' => 'manager', 'name' => 'Manager', 'hierarchy_level' => 50],
            ['slug' => 'auditeur', 'name' => 'Auditeur (hérité)', 'hierarchy_level' => 25],
        ];

        foreach ($roles as $r) {
            Role::query()->updateOrCreate(
                ['slug' => $r['slug']],
                [
                    'name' => $r['name'],
                    'hierarchy_level' => $r['hierarchy_level'],
                    'active' => true,
                ]
            );
        }

        $allPermissionIds = Permission::query()->pluck('id');
        $inspecteur = Role::query()->where('slug', 'inspecteur_services')->first();
        if ($inspecteur) {
            $inspecteur->permissions()->sync($allPermissionIds);
        }

        $subset = Permission::query()->whereIn('slug', ['view', 'create', 'update', 'export'])->pluck('id');
        foreach (['inspecteur_adjoint', 'inspecteur_verificateur', 'risk_manager'] as $slug) {
            $role = Role::query()->where('slug', $slug)->first();
            if ($role) {
                $role->permissions()->sync($subset);
            }
        }

        $charge = Role::query()->where('slug', 'charge_verification')->first();
        if ($charge) {
            $charge->permissions()->sync(Permission::query()->whereIn('slug', ['view', 'create', 'update'])->pluck('id'));
        }

        $deptCompta = Department::query()->where('code', 'COMPTA')->first();
        $roleInspecteur = Role::query()->where('slug', 'inspecteur_services')->first();

        User::query()->updateOrCreate(
            ['email' => 'inspecteur.dgcpt@example.gov'],
            [
                'name' => 'Inspecteur des Services (démo)',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'department_id' => $deptCompta?->id,
                'role_id' => $roleInspecteur?->id,
                'active' => true,
                'position' => 'Inspecteur des Services',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'it.pole@example.gov'],
            [
                'name' => 'Référent SI (démo)',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'department_id' => Department::query()->where('code', 'IT')->value('id'),
                'role_id' => Role::query()->where('slug', 'inspecteur_verificateur')->value('id'),
                'active' => true,
            ]
        );
    }
}
