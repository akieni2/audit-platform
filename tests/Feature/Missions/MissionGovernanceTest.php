<?php

namespace Tests\Feature\Missions;

use App\Models\Department;
use App\Models\Mission;
use App\Models\MissionTeamMember;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissionGovernanceTest extends TestCase
{
    use RefreshDatabase;

    private function createDepartment(string $code = 'POLE1'): Department
    {
        return Department::query()->create([
            'name' => 'Pôle '.$code,
            'code' => $code,
            'type' => 'pole',
            'description' => 'Test',
            'active' => true,
        ]);
    }

    private function role(string $slug): Role
    {
        return Role::query()->create([
            'slug' => $slug,
            'name' => $slug,
            'hierarchy_level' => 50,
            'active' => true,
        ]);
    }

    public function test_normal_agent_cannot_create_mission(): void
    {
        $dept = $this->createDepartment();
        $agent = User::factory()->create([
            'department_id' => $dept->id,
            'approval_status' => 'approved',
            'active' => true,
            'role_id' => null,
        ]);

        $this->actingAs($agent)->get(route('missions.create'))->assertForbidden();

        $this->actingAs($agent)->post(route('missions.store'), [
            'organisation' => 'X',
            'date_debut' => Carbon::today()->format('Y-m-d'),
        ])->assertForbidden();
    }

    public function test_department_supervisor_can_create_mission_and_audit_logged(): void
    {
        $dept = $this->createDepartment();
        $supervisor = User::factory()->create([
            'department_id' => $dept->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);
        $dept->update(['supervisor_user_id' => $supervisor->id]);

        $response = $this->actingAs($supervisor)->post(route('missions.store'), [
            'organisation' => 'Mission superviseur',
            'reference' => 'R-1',
            'date_debut' => Carbon::today()->format('Y-m-d'),
            'date_fin' => null,
            'deadline' => null,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('missions', ['organisation' => 'Mission superviseur']);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $supervisor->id,
            'action' => 'mission_created',
            'module' => 'missions',
        ]);
    }

    public function test_supervisor_can_assign_only_same_department_user(): void
    {
        $dept = $this->createDepartment();
        $supervisor = User::factory()->create([
            'department_id' => $dept->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);
        $dept->update(['supervisor_user_id' => $supervisor->id]);

        $colleague = User::factory()->create([
            'department_id' => $dept->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);

        $mission = Mission::query()->create([
            'organisation' => 'M',
            'date_debut' => Carbon::today(),
            'auditeur_id' => $supervisor->id,
            'department_id' => $dept->id,
            'mission_status' => Mission::STATUS_BROUILLON,
        ]);

        $this->actingAs($supervisor)->post(route('missions.team-members.store', $mission), [
            'user_id' => $colleague->id,
            'mission_role' => MissionTeamMember::ROLE_AGENT,
        ])->assertRedirect(route('missions.show', $mission));

        $this->assertDatabaseHas('mission_team_members', [
            'mission_id' => $mission->id,
            'user_id' => $colleague->id,
        ]);
    }

    public function test_supervisor_cannot_assign_user_from_other_department(): void
    {
        $deptA = $this->createDepartment('A');
        $deptB = $this->createDepartment('B');

        $supervisor = User::factory()->create([
            'department_id' => $deptA->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);
        $deptA->update(['supervisor_user_id' => $supervisor->id]);

        $external = User::factory()->create([
            'department_id' => $deptB->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);

        $mission = Mission::query()->create([
            'organisation' => 'M',
            'date_debut' => Carbon::today(),
            'auditeur_id' => $supervisor->id,
            'department_id' => $deptA->id,
            'mission_status' => Mission::STATUS_BROUILLON,
        ]);

        $this->actingAs($supervisor)->post(route('missions.team-members.store', $mission), [
            'user_id' => $external->id,
            'mission_role' => MissionTeamMember::ROLE_AGENT,
        ])->assertSessionHasErrors('user_id');
    }

    public function test_chef_de_mission_cannot_update_deadlines_gate(): void
    {
        $dept = $this->createDepartment();
        $supervisor = User::factory()->create([
            'department_id' => $dept->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);
        $dept->update(['supervisor_user_id' => $supervisor->id]);

        $chef = User::factory()->create([
            'department_id' => $dept->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);

        $mission = Mission::query()->create([
            'organisation' => 'M',
            'date_debut' => Carbon::today(),
            'auditeur_id' => $chef->id,
            'department_id' => $dept->id,
            'mission_status' => Mission::STATUS_BROUILLON,
        ]);

        MissionTeamMember::query()->create([
            'mission_id' => $mission->id,
            'user_id' => $chef->id,
            'mission_role' => MissionTeamMember::ROLE_CHEF_MISSION,
            'is_lead' => true,
            'assigned_at' => now(),
        ]);

        $this->assertFalse($chef->can('updateDeadlines', $mission));
        $this->assertFalse($chef->can('governMission', $mission));

        $originalDebut = $mission->date_debut?->format('Y-m-d');

        $this->actingAs($chef)->put(route('missions.update', $mission), [
            'description' => 'Nouvelle description',
            'objet' => 'Obj',
            'observations_generales' => 'Obs',
            'date_debut' => Carbon::today()->addMonth()->format('Y-m-d'),
        ])->assertRedirect(route('missions.show', $mission));

        $mission->refresh();
        $this->assertSame($originalDebut, $mission->date_debut?->format('Y-m-d'));
        $this->assertStringContainsString('Nouvelle description', (string) $mission->description);
    }

    public function test_supervisor_can_update_deadlines_and_audit(): void
    {
        $dept = $this->createDepartment();
        $supervisor = User::factory()->create([
            'department_id' => $dept->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);
        $dept->update(['supervisor_user_id' => $supervisor->id]);

        $mission = Mission::query()->create([
            'organisation' => 'M',
            'date_debut' => Carbon::today(),
            'auditeur_id' => $supervisor->id,
            'department_id' => $dept->id,
            'mission_status' => Mission::STATUS_BROUILLON,
        ]);

        $this->assertTrue($supervisor->can('updateDeadlines', $mission));

        $newFin = Carbon::today()->addWeeks(2)->format('Y-m-d');

        $this->actingAs($supervisor)->put(route('missions.update', $mission), [
            'organisation' => 'M',
            'reference' => null,
            'periode_audit' => 'S2',
            'ordre_mission_reference' => null,
            'date_ordre_mission' => null,
            'objet' => null,
            'description' => null,
            'observations_generales' => null,
            'date_debut' => Carbon::today()->format('Y-m-d'),
            'date_fin' => $newFin,
            'deadline' => Carbon::today()->addWeek()->format('Y-m-d'),
        ])->assertRedirect(route('missions.show', $mission));

        $mission->refresh();
        $this->assertSame($newFin, $mission->date_fin?->format('Y-m-d'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $supervisor->id,
            'action' => 'mission_deadlines_updated',
            'module' => 'missions',
        ]);
    }

    public function test_super_admin_retains_full_rights(): void
    {
        $dept = $this->createDepartment();
        $role = $this->role('super_admin');
        $admin = User::factory()->create([
            'department_id' => $dept->id,
            'approval_status' => 'approved',
            'active' => true,
            'role_id' => $role->id,
        ]);

        $this->assertTrue($admin->can('create', Mission::class));

        $response = $this->actingAs($admin)->post(route('missions.store'), [
            'organisation' => 'Mission nationale',
            'date_debut' => Carbon::today()->format('Y-m-d'),
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('missions', ['organisation' => 'Mission nationale']);
    }

    public function test_mission_chef_change_writes_audit(): void
    {
        $dept = $this->createDepartment();
        $supervisor = User::factory()->create([
            'department_id' => $dept->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);
        $dept->update(['supervisor_user_id' => $supervisor->id]);

        $chef1 = User::factory()->create([
            'department_id' => $dept->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);
        $chef2 = User::factory()->create([
            'department_id' => $dept->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);

        $mission = Mission::query()->create([
            'organisation' => 'M',
            'date_debut' => Carbon::today(),
            'auditeur_id' => $chef1->id,
            'department_id' => $dept->id,
            'mission_status' => Mission::STATUS_BROUILLON,
        ]);

        $this->actingAs($supervisor)->post(route('missions.team-members.store', $mission), [
            'user_id' => $chef1->id,
            'mission_role' => MissionTeamMember::ROLE_CHEF_MISSION,
        ]);

        $this->actingAs($supervisor)->post(route('missions.team-members.store', $mission), [
            'user_id' => $chef2->id,
            'mission_role' => MissionTeamMember::ROLE_CHEF_MISSION,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $supervisor->id,
            'action' => 'mission_chef_changed',
            'module' => 'missions',
        ]);
    }
}
