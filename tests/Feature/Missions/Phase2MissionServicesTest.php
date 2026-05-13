<?php

namespace Tests\Feature\Missions;

use App\Models\Department;
use App\Models\Mission;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2MissionServicesTest extends TestCase
{
    use RefreshDatabase;

    private function department(): Department
    {
        return Department::query()->create([
            'name' => 'Pôle P2',
            'code' => 'POLE-P2',
            'type' => 'pole',
            'description' => 'Test',
            'active' => true,
        ]);
    }

    private function supervisor(Department $dept): User
    {
        $user = User::factory()->create([
            'department_id' => $dept->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);
        $dept->update(['supervisor_user_id' => $user->id]);

        return $user;
    }

    public function test_supervisor_can_create_mission_service_via_mission_scoped_route(): void
    {
        $dept = $this->department();
        $supervisor = $this->supervisor($dept);

        $mission = Mission::query()->create([
            'organisation' => 'Org P2',
            'description' => 'd',
            'date_debut' => Carbon::today(),
            'date_fin' => null,
            'auditeur_id' => $supervisor->id,
            'department_id' => $dept->id,
            'mission_status' => Mission::STATUS_BROUILLON,
        ]);

        $response = $this->actingAs($supervisor)->post(route('missions.services.store', $mission), [
            'nom' => 'Service Finances',
            'code' => 'FIN',
            'service_type' => 'finances',
            'active' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('services', [
            'mission_id' => $mission->id,
            'nom' => 'Service Finances',
            'code' => 'FIN',
        ]);
    }

    public function test_agent_without_governance_cannot_create_service(): void
    {
        $dept = $this->department();
        $supervisor = $this->supervisor($dept);

        $mission = Mission::query()->create([
            'organisation' => 'Org P2',
            'description' => 'd',
            'date_debut' => Carbon::today(),
            'date_fin' => null,
            'auditeur_id' => $supervisor->id,
            'department_id' => $dept->id,
            'mission_status' => Mission::STATUS_BROUILLON,
        ]);

        $agent = User::factory()->create([
            'department_id' => $dept->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);

        $this->actingAs($agent)->post(route('missions.services.store', $mission), [
            'nom' => 'Tentative',
        ])->assertForbidden();
    }

    public function test_national_inspector_can_store_department_consolidation(): void
    {
        $role = Role::query()->create([
            'slug' => 'inspecteur_services',
            'name' => 'Inspecteur des Services',
            'hierarchy_level' => 100,
            'active' => true,
        ]);
        $user = User::factory()->create([
            'department_id' => null,
            'role_id' => $role->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);

        $dept = $this->department();
        $mission = Mission::query()->create([
            'organisation' => 'Org national',
            'description' => 'd',
            'date_debut' => Carbon::today(),
            'date_fin' => null,
            'auditeur_id' => $user->id,
            'department_id' => $dept->id,
            'mission_status' => Mission::STATUS_BROUILLON,
        ]);

        $this->actingAs($user)->post(route('missions.consolidations.store', $mission), [
            'synthesis' => 'Synthèse test',
            'global_risk_level' => 'Modéré',
        ])->assertRedirect();

        $this->assertDatabaseHas('department_audit_consolidations', [
            'mission_id' => $mission->id,
        ]);
    }

    public function test_service_destroy_archives_row_with_soft_delete(): void
    {
        $dept = $this->department();
        $supervisor = $this->supervisor($dept);

        $mission = Mission::query()->create([
            'organisation' => 'Org archive',
            'description' => 'd',
            'date_debut' => Carbon::today(),
            'date_fin' => null,
            'auditeur_id' => $supervisor->id,
            'department_id' => $dept->id,
            'mission_status' => Mission::STATUS_BROUILLON,
        ]);

        $service = Service::query()->create([
            'mission_id' => $mission->id,
            'nom' => 'Service à archiver',
        ]);

        $this->actingAs($supervisor)
            ->delete(route('missions.services.destroy', [$mission, $service]))
            ->assertRedirect();

        $this->assertSoftDeleted('services', [
            'id' => $service->id,
        ]);
    }
}
