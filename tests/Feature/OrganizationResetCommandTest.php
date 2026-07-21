<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationResetCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_preserves_super_admin_and_removes_other_users_and_departments(): void
    {
        $superRole = Role::query()->create(['slug' => 'super_admin', 'name' => 'Super administrateur', 'hierarchy_level' => 1000, 'active' => true]);
        $userRole = Role::query()->create(['slug' => 'agent_operationnel', 'name' => 'Agent', 'hierarchy_level' => 10, 'active' => true]);
        $super = User::factory()->create(['email' => config('dgcpt.super_admin_email'), 'role_id' => $superRole->id, 'role' => 'admin']);
        $department = Department::query()->create(['name' => 'Direction', 'code' => 'DIR', 'type' => 'direction', 'active' => true]);
        $regular = User::factory()->create(['role_id' => $userRole->id, 'role' => 'agent_operationnel', 'department_id' => $department->id]);

        $this->artisan('organization:reset', [
            '--confirm' => 'PURGER-ORGANISATION-DGCPT',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertNotNull(User::withTrashed()->find($super->id));
        $this->assertNull(User::withTrashed()->find($regular->id));
        $this->assertSame(0, Department::query()->count());
        $this->assertNull($super->fresh()->department_id);
    }

    public function test_reset_refuses_an_incorrect_confirmation_phrase(): void
    {
        $superRole = Role::query()->create(['slug' => 'super_admin', 'name' => 'Super administrateur', 'hierarchy_level' => 1000, 'active' => true]);
        User::factory()->create(['email' => config('dgcpt.super_admin_email'), 'role_id' => $superRole->id, 'role' => 'admin']);
        Department::query()->create(['name' => 'Direction', 'code' => 'DIR', 'type' => 'direction', 'active' => true]);

        $this->artisan('organization:reset', ['--confirm' => 'INCORRECT'])->assertFailed();

        $this->assertSame(1, Department::query()->count());
    }
}
