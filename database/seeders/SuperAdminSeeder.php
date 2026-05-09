<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crée le compte système Super Administrateur (initialisation plateforme DGCPT).
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('dgcpt.super_admin_email', 'admin@dgcpt.ga');

        if (User::query()->where('email', $email)->exists()) {
            if ($this->command !== null) {
                $this->command->info('Super administrateur déjà présent — '.$email.' (aucune action).');
            }

            return;
        }

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
            throw new \RuntimeException('Le rôle super_admin est introuvable. Exécutez DgcptFoundationSeeder avant SuperAdminSeeder.');
        }

        $plainPassword = 'TmpSeed'.bin2hex(random_bytes(12)).'Aa1!';

        User::query()->create([
            'name' => 'Super Administrateur',
            'email' => $email,
            'password' => Hash::make($plainPassword),
            'role' => 'admin',
            'department_id' => $department->id,
            'role_id' => $role->id,
            'active' => true,
            'must_change_password' => true,
            'password_changed_at' => null,
            'password_expires_at' => null,
            'position' => 'Super Administrateur système',
        ]);

        if ($this->command !== null) {
            $this->command->warn('═══════════════════════════════════════════════════════════');
            $this->command->warn(' Compte Super Administrateur créé.');
            $this->command->warn(' Email    : '.$email);
            $this->command->warn(' MDP temp : '.$plainPassword);
            $this->command->warn(' Changez ce mot de passe à la première connexion.');
            $this->command->warn('═══════════════════════════════════════════════════════════');
        }
    }
}
