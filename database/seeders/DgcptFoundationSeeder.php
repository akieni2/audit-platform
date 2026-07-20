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

        $directionGenerale = Department::query()->updateOrCreate(
            ['code' => 'DG'],
            [
                'name' => 'Direction Générale de la Comptabilité Publique et du Trésor',
                'type' => 'direction_generale',
                'description' => 'Sommet de l’organigramme institutionnel DGCPT.',
                'active' => true,
                'parent_department_id' => null,
                'governance_scope' => 'national',
                'executive_visibility' => true,
                'intelligence_profile' => [
                    'position_title' => 'Directeur Général',
                    'position_description' => 'Autorité supérieure de pilotage, supervision et arbitrage institutionnel.',
                    'position_activities' => [
                        'Définir les orientations stratégiques',
                        'Valider les dispositifs de gouvernance et de contrôle',
                        'Superviser la consolidation nationale des risques',
                    ],
                    'top_manager_profile' => [
                        'title' => 'Directeur Général',
                    ],
                ],
            ]
        );

        Department::query()
            ->whereIn('code', ['COMPTA', 'RISQUES', 'PILOTAGE', 'IT'])
            ->whereNull('parent_department_id')
            ->update([
                'parent_department_id' => $directionGenerale->id,
                'governance_scope' => 'inspection',
                'executive_visibility' => true,
            ]);

        $permissionSlugs = [
            ['slug' => 'view', 'name' => 'Consulter', 'group' => 'core'],
            ['slug' => 'create', 'name' => 'Créer', 'group' => 'core'],
            ['slug' => 'update', 'name' => 'Modifier', 'group' => 'core'],
            ['slug' => 'delete', 'name' => 'Supprimer', 'group' => 'core'],
            ['slug' => 'validate', 'name' => 'Valider', 'group' => 'workflow'],
            ['slug' => 'escalate', 'name' => 'Escalader', 'group' => 'workflow'],
            ['slug' => 'supervise', 'name' => 'Superviser', 'group' => 'executive'],
            ['slug' => 'export', 'name' => 'Exporter', 'group' => 'reporting'],
            ['slug' => 'view_department_data', 'name' => 'Voir données du département', 'group' => 'iam'],
            ['slug' => 'view_shared_data', 'name' => 'Voir données partagées', 'group' => 'iam'],
            ['slug' => 'view_global_dashboard', 'name' => 'Tableau de bord global', 'group' => 'iam'],
            ['slug' => 'create_mission', 'name' => 'Créer mission', 'group' => 'iam'],
            ['slug' => 'update_mission', 'name' => 'Modifier mission', 'group' => 'iam'],
            ['slug' => 'delete_mission', 'name' => 'Supprimer mission', 'group' => 'iam'],
            ['slug' => 'validate_mission', 'name' => 'Valider mission', 'group' => 'iam'],
            ['slug' => 'create_risk', 'name' => 'Créer risque', 'group' => 'iam'],
            ['slug' => 'transfer_risk', 'name' => 'Transférer risque', 'group' => 'iam'],
            ['slug' => 'manage_users', 'name' => 'Gérer utilisateurs', 'group' => 'iam'],
            ['slug' => 'manage_departments', 'name' => 'Gérer départements', 'group' => 'iam'],
            ['slug' => 'export_reports', 'name' => 'Exporter rapports institutionnels', 'group' => 'iam'],
            ['slug' => 'manage_roles', 'name' => 'Gérer rôles', 'group' => 'iam'],
            ['slug' => 'supervise_department', 'name' => 'Superviser un département', 'group' => 'iam'],
            ['slug' => 'supervise_global', 'name' => 'Supervision nationale', 'group' => 'iam'],
        ];

        foreach ($permissionSlugs as $p) {
            Permission::query()->updateOrCreate(
                ['slug' => $p['slug']],
                ['name' => $p['name'], 'group' => $p['group']]
            );
        }

        $roles = [
            ['slug' => 'super_admin', 'name' => 'Super administrateur technique', 'hierarchy_level' => 110],
            ['slug' => 'copri', 'name' => 'COPRI — Pilotage stratégique national', 'hierarchy_level' => 105],
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
        $superAdminRole = Role::query()->where('slug', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdminRole->permissions()->sync($allPermissionIds);
        }

        $inspecteur = Role::query()->where('slug', 'inspecteur_services')->first();
        if ($inspecteur) {
            $inspecteur->permissions()->sync($allPermissionIds);
        }

        $copriRole = Role::query()->where('slug', 'copri')->first();
        if ($copriRole) {
            $copriRole->permissions()->sync(
                Permission::query()->whereIn('slug', ['view', 'export', 'view_global_dashboard'])->pluck('id')
            );
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
                'password_changed_at' => now(),
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
                'password_changed_at' => now(),
                'role' => 'manager',
                'department_id' => Department::query()->where('code', 'IT')->value('id'),
                'role_id' => Role::query()->where('slug', 'inspecteur_verificateur')->value('id'),
                'active' => true,
            ]
        );
    }
}
