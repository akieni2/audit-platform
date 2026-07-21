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
}
