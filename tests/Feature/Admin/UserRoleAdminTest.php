<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\UserRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_admin_users_page(): void
    {
        $user = User::factory()->create(['role' => UserRoles::AUDITEUR]);

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_assign_risk_manager_role(): void
    {
        $admin = User::factory()->create(['role' => UserRoles::ADMIN]);
        $target = User::factory()->create(['role' => UserRoles::AUDITEUR]);

        $this->actingAs($admin)
            ->patch(route('admin.users.role.update', $target), [
                'role' => UserRoles::RISK_MANAGER,
            ])
            ->assertRedirect();

        $this->assertSame(UserRoles::RISK_MANAGER, $target->fresh()->role);
    }

    public function test_admin_cannot_remove_own_admin_role(): void
    {
        $admin = User::factory()->create(['role' => UserRoles::ADMIN]);

        $this->actingAs($admin)
            ->patch(route('admin.users.role.update', $admin), [
                'role' => UserRoles::AUDITEUR,
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame(UserRoles::ADMIN, $admin->fresh()->role);
    }
}
