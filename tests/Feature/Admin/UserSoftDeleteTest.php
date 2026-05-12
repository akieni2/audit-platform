<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function superAdminRole(): Role
    {
        return Role::query()->create([
            'slug' => 'super_admin',
            'name' => 'Super administrateur',
            'hierarchy_level' => 110,
            'active' => true,
        ]);
    }

    public function test_legacy_admin_cannot_soft_delete_user(): void
    {
        $roleAdmin = Role::query()->create([
            'slug' => 'admin',
            'name' => 'Administrateur',
            'hierarchy_level' => 90,
            'active' => true,
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'role_id' => $roleAdmin->id,
            'approval_status' => 'approved',
        ]);

        $target = User::factory()->create(['approval_status' => 'approved']);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target))
            ->assertForbidden();
    }

    public function test_super_admin_can_soft_delete_non_super_user(): void
    {
        $superRole = $this->superAdminRole();

        $superAdmin = User::factory()->create([
            'role_id' => $superRole->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);

        $target = User::factory()->create([
            'approval_status' => 'approved',
            'active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted('users', ['id' => $target->id]);
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'active' => false,
            'deleted_by' => $superAdmin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superAdmin->id,
            'action' => 'user_soft_deleted',
        ]);
    }

    public function test_cannot_soft_delete_own_account(): void
    {
        $superRole = $this->superAdminRole();

        $superAdmin = User::factory()->create([
            'role_id' => $superRole->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->delete(route('admin.users.destroy', $superAdmin))
            ->assertSessionHasErrors();
    }

    public function test_can_soft_delete_super_admin_when_another_active_super_admin_exists(): void
    {
        $superRole = $this->superAdminRole();

        $superA = User::factory()->create([
            'role_id' => $superRole->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);

        $superB = User::factory()->create([
            'role_id' => $superRole->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);

        $this->actingAs($superA)
            ->delete(route('admin.users.destroy', $superB))
            ->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted('users', ['id' => $superB->id]);
    }
}
