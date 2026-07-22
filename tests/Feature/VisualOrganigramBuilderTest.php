<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\MethodologyTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisualOrganigramBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_move_and_assign_a_position_from_the_visual_builder(): void
    {
        $role = Role::query()->create(['slug' => 'super_admin', 'name' => 'Super administrateur', 'hierarchy_level' => 1000, 'active' => true]);
        $user = User::factory()->create(['role_id' => $role->id, 'role' => 'admin']);
        $root = Department::query()->create(['name' => 'Direction générale', 'code' => 'DG', 'type' => 'direction_generale', 'active' => true]);
        $methodology = MethodologyTemplate::query()->create([
            'name' => 'Référentiel institutionnel', 'slug' => 'ref-institutionnel', 'framework_key' => 'DGCPT',
            'active' => true, 'is_system' => false, 'is_global' => true, 'version' => 1, 'lifecycle_status' => 'published',
        ]);

        $this->actingAs($user)->get(route('admin.departments.organigramme'))
            ->assertOk()->assertSee('Organisation institutionnelle dynamique')->assertSee('Objets administratifs');

        $response = $this->actingAs($user)->postJson(route('admin.departments.visual-store'), [
            'name' => 'Direction des Systèmes d’Information', 'code' => 'DSI', 'type' => 'direction',
            'parent_department_id' => $root->id, 'default_methodology_template_id' => $methodology->id,
        ])->assertCreated();

        $direction = Department::query()->findOrFail($response->json('id'));
        $this->assertSame($root->id, $direction->parent_department_id);
        $this->assertSame('ready', data_get($direction->intelligence_profile, 'audit_environment.status'));

        $this->actingAs($user)->patchJson(route('admin.departments.visual-position', $direction), ['position_title' => 'Directeur'])->assertOk();
        $this->assertSame('Directeur', $direction->fresh()->headTitle());

        $newSupervisor = User::factory()->create([
            'department_id' => $direction->id,
            'approval_status' => User::APPROVAL_STATUS_APPROVED,
            'active' => true,
        ]);
        $this->actingAs($user)->patchJson(route('admin.departments.visual-supervisor', $direction), [
            'supervisor_user_id' => $newSupervisor->id,
        ])->assertOk()->assertJsonPath('supervisor_name', $newSupervisor->displayName());
        $this->assertSame($newSupervisor->id, $direction->fresh()->supervisor_user_id);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'department_supervisor_changed',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->patchJson(route('admin.departments.visual-move', $direction), ['parent_department_id' => null])->assertOk();
        $this->assertNull($direction->fresh()->parent_department_id);
    }

    public function test_visual_builder_rejects_an_incompatible_parent(): void
    {
        $role = Role::query()->create(['slug' => 'super_admin', 'name' => 'Super administrateur', 'hierarchy_level' => 1000, 'active' => true]);
        $user = User::factory()->create(['role_id' => $role->id, 'role' => 'admin']);
        $cell = Department::query()->create(['name' => 'Cellule', 'code' => 'CELL', 'type' => 'cellule', 'active' => true]);
        $direction = Department::query()->create(['name' => 'Direction', 'code' => 'DIR', 'type' => 'direction', 'active' => true]);

        $this->actingAs($user)->patchJson(route('admin.departments.visual-move', $direction), ['parent_department_id' => $cell->id])->assertUnprocessable();
    }

    public function test_department_supervisor_builds_only_inside_own_functional_chart(): void
    {
        $role = Role::query()->create(['slug' => 'directeur', 'name' => 'Directeur', 'hierarchy_level' => 90, 'active' => true]);
        $root = Department::query()->create(['name' => 'Direction informatique', 'code' => 'DSI', 'type' => 'direction', 'active' => true]);
        $other = Department::query()->create(['name' => 'Direction financière', 'code' => 'DF', 'type' => 'direction', 'active' => true]);
        $supervisor = User::factory()->create(['role_id' => $role->id, 'role' => 'directeur', 'department_id' => $root->id]);
        $root->update(['supervisor_user_id' => $supervisor->id]);

        $this->actingAs($supervisor)->get(route('admin.departments.organigramme'))
            ->assertOk()->assertSee('Organigramme fonctionnel du département')->assertSee('Direction informatique')->assertDontSee('Direction financière');

        $response = $this->actingAs($supervisor)->postJson(route('admin.departments.visual-store'), [
            'name' => 'Service infrastructure', 'code' => 'INFRA', 'type' => 'service', 'parent_department_id' => $root->id,
        ])->assertCreated();
        $service = Department::query()->findOrFail($response->json('id'));
        $this->assertSame($root->id, $service->parent_department_id);

        $this->actingAs($supervisor)->patchJson(route('admin.departments.visual-move', $service), [
            'parent_department_id' => $other->id,
        ])->assertForbidden();

        $externalSupervisor = User::factory()->create([
            'department_id' => $other->id,
            'approval_status' => User::APPROVAL_STATUS_APPROVED,
            'active' => true,
        ]);
        $this->actingAs($supervisor)->patchJson(route('admin.departments.visual-supervisor', $service), [
            'supervisor_user_id' => $externalSupervisor->id,
        ])->assertForbidden();
    }

    public function test_department_user_can_view_local_chart_but_cannot_build_it(): void
    {
        $role = Role::query()->create(['slug' => 'agent_operationnel', 'name' => 'Agent', 'hierarchy_level' => 10, 'active' => true]);
        $root = Department::query()->create(['name' => 'Direction informatique', 'code' => 'DSI', 'type' => 'direction', 'active' => true]);
        $user = User::factory()->create(['role_id' => $role->id, 'role' => 'agent_operationnel', 'department_id' => $root->id]);

        $this->actingAs($user)->get(route('admin.departments.organigramme'))
            ->assertOk()->assertSee('Direction informatique')->assertSee('Son responsable est habilité');

        $this->actingAs($user)->postJson(route('admin.departments.visual-store'), [
            'name' => 'Cellule interdite', 'code' => 'NOPE', 'type' => 'cellule', 'parent_department_id' => $root->id,
        ])->assertForbidden();
    }

    public function test_human_resources_user_can_view_the_global_chart(): void
    {
        $role = Role::query()->create(['slug' => 'ressources_humaines', 'name' => 'Ressources humaines', 'hierarchy_level' => 70, 'active' => true]);
        $hr = Department::query()->create(['name' => 'Direction des ressources humaines', 'code' => 'DRH', 'type' => 'direction', 'active' => true]);
        Department::query()->create(['name' => 'Direction informatique', 'code' => 'DSI', 'type' => 'direction', 'active' => true]);
        $user = User::factory()->create(['role_id' => $role->id, 'role' => 'manager', 'department_id' => $hr->id]);

        $this->actingAs($user)->get(route('admin.departments.organigramme'))
            ->assertOk()->assertSee('Organigramme institutionnel global')->assertSee('Direction informatique');
    }

    public function test_super_admin_permanently_deletes_a_confirmed_department_tree(): void
    {
        $role = Role::query()->create(['slug' => 'super_admin', 'name' => 'Super administrateur', 'hierarchy_level' => 1000, 'active' => true]);
        $user = User::factory()->create(['role_id' => $role->id, 'role' => 'admin']);
        $root = Department::query()->create(['name' => 'Administration', 'code' => 'ADM', 'type' => 'administration', 'active' => true]);
        Department::query()->create(['name' => 'Direction', 'code' => 'DIR', 'type' => 'direction', 'active' => true, 'parent_department_id' => $root->id]);

        $this->actingAs($user)->delete(route('admin.departments.destroy', $root), [
            'confirmation_code' => 'ADM',
        ])->assertRedirect(route('admin.departments.index'));

        $this->assertSame(0, Department::query()->count());
    }
}
