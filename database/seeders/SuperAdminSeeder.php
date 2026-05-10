<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crée ou corrige le compte Super Administrateur (IAM : rôle institutionnel super_admin + pôle Administration Centrale).
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = strtolower(trim((string) config('dgcpt.super_admin_email', 'admin@dgcpt.ga')));

        $department = Department::query()->updateOrCreate(
            ['code' => 'ADMIN_CENT'],
            [
                'name' => 'Administration Centrale',
                'type' => 'administration',
                'description' => 'Pilotage et administration centrale de la plateforme DGCPT.',
                'active' => true,
            ]
        );

        $role = Role::query()->where('slug', 'super_admin')->first();
        if ($role === null) {
            throw new \RuntimeException(
                'Le rôle institutionnel « super_admin » est introuvable. Exécutez DgcptFoundationSeeder avant SuperAdminSeeder.'
            );
        }

        $iamCore = [
            'department_id' => $department->id,
            'role_id' => $role->id,
            'role' => 'admin',
            'active' => true,
            'position' => 'Super Administrateur système',
        ];

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user === null) {
            $plainPassword = 'TmpSeed'.bin2hex(random_bytes(12)).'Aa1!';

            User::query()->create(array_merge($iamCore, [
                'name' => 'Super Administrateur',
                'email' => $email,
                'password' => Hash::make($plainPassword),
                'must_change_password' => true,
                'password_changed_at' => null,
                'password_expires_at' => null,
            ]));

            if ($this->command !== null) {
                $this->command->warn('═══════════════════════════════════════════════════════════');
                $this->command->warn(' Compte Super Administrateur créé.');
                $this->command->warn(' Email    : '.$email);
                $this->command->warn(' MDP temp : '.$plainPassword);
                $this->command->warn(' role_id  : '.$role->id.' (super_admin) · department_id : '.$department->id.' (ADMIN_CENT)');
                $this->command->warn(' Changez ce mot de passe à la première connexion.');
                $this->command->warn('═══════════════════════════════════════════════════════════');
            }

            return;
        }

        $user->forceFill(array_merge($iamCore, [
            'email' => $email,
        ]));

        if ($user->isDirty()) {
            $user->save();

            if ($this->command !== null) {
                $this->command->info(
                    'Super administrateur existant — IAM réaligné : role_id='.$role->id.' (super_admin), department_id='.$department->id.' (Administration Centrale).'
                );
            }
        } elseif ($this->command !== null) {
            $this->command->info('Super administrateur déjà conforme (IAM) — '.$email);
        }
    }
}
