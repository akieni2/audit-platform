<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\MobileMenuAssignment;
use App\Models\Role;
use App\Models\User;
use App\Services\MobileMenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class MobileMenuAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_android_application_is_detected_without_affecting_browser_requests(): void
    {
        $service = app(MobileMenuService::class);
        $android = Request::create('/', 'GET', server: ['HTTP_USER_AGENT' => 'Android WebView DGCPT-Android/1.0']);
        $browser = Request::create('/', 'GET', server: ['HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome/140']);

        $this->assertTrue($service->isMobileApplication($android));
        $this->assertFalse($service->isMobileApplication($browser));
    }

    public function test_role_and_parent_department_assignments_are_combined(): void
    {
        $role = Role::query()->create(['slug' => 'agent_test', 'name' => 'Agent test', 'hierarchy_level' => 10, 'active' => true]);
        $parent = Department::query()->create(['code' => 'PARENT', 'name' => 'Structure parente', 'type' => 'pole', 'active' => true]);
        $child = Department::query()->create(['code' => 'CHILD', 'name' => 'Structure enfant', 'type' => 'division', 'active' => true, 'parent_department_id' => $parent->id]);
        $user = User::factory()->create(['role_id' => $role->id, 'department_id' => $child->id, 'role' => 'agent_test']);

        MobileMenuAssignment::query()->create(['subject_type' => 'role', 'subject_id' => $role->id, 'menu_key' => 'missions']);
        MobileMenuAssignment::query()->create(['subject_type' => 'department', 'subject_id' => $parent->id, 'menu_key' => 'questionnaires']);

        $keys = app(MobileMenuService::class)->allowedKeys($user);

        $this->assertEqualsCanonicalizing(['missions', 'questionnaires'], $keys);
    }

    public function test_individual_assignment_replaces_collective_assignments(): void
    {
        $role = Role::query()->create(['slug' => 'agent_mobile', 'name' => 'Agent mobile', 'hierarchy_level' => 10, 'active' => true]);
        $department = Department::query()->create(['code' => 'MOB', 'name' => 'Mobile', 'type' => 'division', 'active' => true]);
        $user = User::factory()->create(['role_id' => $role->id, 'department_id' => $department->id, 'role' => 'agent_mobile']);

        MobileMenuAssignment::query()->create(['subject_type' => 'role', 'subject_id' => $role->id, 'menu_key' => 'missions']);
        MobileMenuAssignment::query()->create(['subject_type' => 'user', 'subject_id' => $user->id, 'menu_key' => 'cfdt']);

        $service = app(MobileMenuService::class);

        $this->assertSame(['cfdt'], $service->allowedKeys($user));
        $this->assertTrue($service->canAccessRoute($user, 'cfdt.index'));
        $this->assertFalse($service->canAccessRoute($user, 'missions.index'));
        $this->assertTrue($service->canAccessRoute($user, 'profile.edit'));
    }

    public function test_expired_assignment_is_ignored(): void
    {
        $role = Role::query()->create(['slug' => 'expired_mobile', 'name' => 'Expiré', 'hierarchy_level' => 10, 'active' => true]);
        $user = User::factory()->create(['role_id' => $role->id, 'role' => 'expired_mobile']);
        MobileMenuAssignment::query()->create([
            'subject_type' => 'user',
            'subject_id' => $user->id,
            'menu_key' => 'missions',
            'expires_at' => now()->subMinute(),
        ]);

        $this->assertSame([], app(MobileMenuService::class)->allowedKeys($user));
    }

    public function test_super_admin_can_manage_mobile_assignments_without_changing_web_roles(): void
    {
        $superRole = Role::query()->create(['slug' => 'super_admin', 'name' => 'Super Admin', 'hierarchy_level' => 100, 'active' => true]);
        $targetRole = Role::query()->create(['slug' => 'inspecteur_mobile', 'name' => 'Inspecteur mobile', 'hierarchy_level' => 20, 'active' => true]);
        $admin = User::factory()->create(['role_id' => $superRole->id, 'role' => 'super_admin']);

        $this->actingAs($admin)
            ->get(route('admin.mobile-menu.index'))
            ->assertOk()
            ->assertSee('Menus de l’application Android');

        $this->actingAs($admin)
            ->post(route('admin.mobile-menu.store'), [
                'subject_type' => 'role',
                'subject_id' => $targetRole->id,
                'menus' => ['missions', 'questionnaires'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('mobile_menu_assignments', [
            'subject_type' => 'role',
            'subject_id' => $targetRole->id,
            'menu_key' => 'missions',
        ]);
        $this->assertDatabaseHas('mobile_menu_assignments', [
            'subject_type' => 'role',
            'subject_id' => $targetRole->id,
            'menu_key' => 'questionnaires',
        ]);
    }
}
