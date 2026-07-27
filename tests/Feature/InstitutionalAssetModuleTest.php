<?php

namespace Tests\Feature;

use App\Models\AssetModuleAccess;
use App\Models\Department;
use App\Models\InstitutionalAsset;
use App\Models\InstitutionalAssetCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionalAssetModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_structure_grant_is_inherited_and_can_be_revoked(): void
    {
        $admin = $this->admin();
        $d = $this->dept('DSI');
        $s = $this->dept('INFRA', $d->id);
        $u = User::factory()->create(['department_id' => $s->id]);
        AssetModuleAccess::create(['department_id' => $d->id, 'default_role' => 'contributor', 'inherit_to_children' => true, 'active' => true, 'granted_by' => $admin->id]);
        $this->assertTrue($u->canAccessInstitutionalAssets());
        AssetModuleAccess::create(['department_id' => $s->id, 'default_role' => 'viewer', 'inherit_to_children' => true, 'active' => false, 'granted_by' => $admin->id]);
        $this->assertFalse($u->fresh()->canAccessInstitutionalAssets());
    }

    public function test_contributor_registers_asset_with_automatic_criticality_and_dependencies(): void
    {
        $admin = $this->admin();
        $d = $this->dept('PI');
        $u = User::factory()->create(['department_id' => $d->id, 'active' => true, 'approval_status' => User::APPROVAL_STATUS_APPROVED]);
        AssetModuleAccess::create(['department_id' => $d->id, 'default_role' => 'contributor', 'inherit_to_children' => true, 'active' => true, 'granted_by' => $admin->id]);
        $cat = InstitutionalAssetCategory::create(['owner_department_id' => $d->id, 'code' => 'SERV', 'name' => 'Serveurs', 'active' => true, 'created_by' => $admin->id]);
        $base = InstitutionalAsset::create(['category_id' => $cat->id, 'owner_department_id' => $d->id, 'asset_tag' => 'AST-BASE', 'name' => 'Pare-feu', 'status' => 'active', 'created_by' => $admin->id]);
        $response = $this->actingAs($u)->post(route('institutional-assets.store'), ['category_id' => $cat->id, 'owner_department_id' => $d->id, 'asset_tag' => 'AST-IT-001', 'name' => 'Active Directory', 'condition' => 'good', 'status' => 'active', 'availability_score' => 5, 'confidentiality_score' => 4, 'integrity_score' => 5, 'traceability_score' => 3, 'probability_score' => 4, 'visibility' => 'participants', 'has_backup' => 1, 'single_point_of_failure' => 1]);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $asset = InstitutionalAsset::where('asset_tag', 'AST-IT-001')->firstOrFail();
        $response->assertRedirect(route('institutional-assets.show', $asset));
        $this->assertSame(20, $asset->criticality_score);
        $this->assertSame('critical', $asset->criticality);
        $this->assertSame('draft', $asset->status);
        $this->assertTrue($asset->has_backup);
        $this->actingAs($u)->post(route('institutional-assets.dependencies.store', $asset), ['depends_on_asset_id' => $base->id, 'dependency_type' => 'réseau', 'critical' => 1])->assertRedirect();
        $this->actingAs($u)->post(route('institutional-assets.controls.store', $asset), ['name' => 'Sauvegarde', 'status' => 'implemented'])->assertRedirect();
        $this->assertDatabaseCount('institutional_asset_dependencies', 1);
        $this->assertDatabaseCount('institutional_asset_controls', 1);
        $this->assertDatabaseCount('institutional_asset_history', 3);
    }

    private function dept(string $code, ?int $parent = null): Department
    {
        return Department::create(['name' => $code, 'code' => $code, 'type' => $parent ? 'service' : 'direction', 'active' => true, 'parent_department_id' => $parent]);
    }

    private function admin(): User
    {
        $r = Role::create(['slug' => 'super_admin', 'name' => 'Super administrateur', 'hierarchy_level' => 100, 'active' => true]);

        return User::factory()->create(['role_id' => $r->id, 'role' => 'admin', 'active' => true, 'approval_status' => User::APPROVAL_STATUS_APPROVED]);
    }
}
