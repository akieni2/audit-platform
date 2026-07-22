<?php

namespace Tests\Feature\Iam;

use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CopriMenuAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_structure_supervisor_inherits_copri_access(): void
    {
        $department = Department::query()->create([
            'name' => 'Pôle informatique',
            'code' => 'PI',
            'type' => 'pole',
            'active' => true,
        ]);
        $supervisor = User::factory()->create(['department_id' => $department->id]);
        $department->update(['supervisor_user_id' => $supervisor->id]);

        $this->assertTrue($supervisor->canAccessCopriMenu());
        $this->assertTrue($supervisor->can('viewExecutiveDashboard'));
    }

    public function test_verifier_roles_receive_copri_access_permission(): void
    {
        $permission = Permission::query()->where('slug', 'access_copri_menu')->firstOrFail();

        foreach (['inspecteur_verificateur', 'inspecteur_verificateur_adjoint'] as $slug) {
            $role = Role::query()->create([
                'slug' => $slug,
                'name' => $slug,
                'hierarchy_level' => 50,
                'active' => true,
            ]);
            $role->permissions()->attach($permission);
            $user = User::factory()->create(['role_id' => $role->id]);

            $this->assertTrue($user->canAccessCopriMenu());
        }
    }

    public function test_individual_override_can_revoke_or_grant_access(): void
    {
        $permission = Permission::query()->where('slug', 'access_copri_menu')->firstOrFail();
        $role = Role::query()->create([
            'slug' => 'directeur_test',
            'name' => 'Directeur test',
            'hierarchy_level' => 80,
            'active' => true,
        ]);
        $role->permissions()->attach($permission);
        $director = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($director->canAccessCopriMenu());

        $director->update(['copri_menu_enabled' => false]);
        $this->assertFalse($director->fresh()->canAccessCopriMenu());
        $this->assertFalse($director->fresh()->can('viewExecutiveDashboard'));

        $ordinaryUser = User::factory()->create(['copri_menu_enabled' => true]);
        $this->assertTrue($ordinaryUser->canAccessCopriMenu());
    }
}
