<?php

namespace Tests\Feature\Missions;

use App\Models\Department;
use App\Models\Mission;
use App\Models\MissionTeamMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissionOrdreMissionPhase1Test extends TestCase
{
    use RefreshDatabase;

    private function seedDeptMissionAndActor(): array
    {
        $dept = Department::query()->create([
            'name' => 'Pôle Test',
            'code' => 'POLET',
            'type' => 'pole',
            'description' => 'Test',
            'active' => true,
        ]);

        $actor = User::factory()->create([
            'department_id' => $dept->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);

        $dept->update(['supervisor_user_id' => $actor->id]);

        $auditeur = User::factory()->create([
            'department_id' => $dept->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);

        $mission = Mission::query()->create([
            'organisation' => 'Mission test',
            'description' => null,
            'date_debut' => Carbon::today(),
            'date_fin' => null,
            'auditeur_id' => $auditeur->id,
            'department_id' => $dept->id,
            'mission_status' => Mission::STATUS_BROUILLON,
        ]);

        return [$dept, $actor, $auditeur, $mission];
    }

    public function test_can_update_ordre_mission_fields_and_audit_logged(): void
    {
        [, $actor,, $mission] = $this->seedDeptMissionAndActor();

        $response = $this->actingAs($actor)->put(route('missions.update', $mission), [
            'organisation' => 'Mission test MAJ',
            'reference' => 'REF-2026-001',
            'objet' => 'Audit institutionnel',
            'description' => 'Desc',
            'periode_audit' => 'T1 2026',
            'ordre_mission_reference' => 'OM-001',
            'date_ordre_mission' => '2026-05-01',
            'observations_generales' => 'Obs.',
            'date_debut' => Carbon::today()->format('Y-m-d'),
            'date_fin' => Carbon::today()->addWeek()->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('missions.show', $mission));

        $mission->refresh();
        $this->assertSame('REF-2026-001', $mission->reference);
        $this->assertSame('Audit institutionnel', $mission->objet);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $actor->id,
            'action' => 'mission_ordre_updated',
            'module' => 'missions',
        ]);
    }

    public function test_can_add_and_remove_team_member(): void
    {
        [$dept, $actor, $auditeur, $mission] = $this->seedDeptMissionAndActor();

        $colleague = User::factory()->create([
            'department_id' => $dept->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);

        $store = $this->actingAs($actor)->post(route('missions.team-members.store', $mission), [
            'user_id' => $colleague->id,
            'mission_role' => MissionTeamMember::ROLE_INSPECTEUR_VERIFICATEUR,
            'designation' => null,
        ]);

        $store->assertRedirect(route('missions.show', $mission));

        $member = MissionTeamMember::query()->where('mission_id', $mission->id)->where('user_id', $colleague->id)->first();
        $this->assertNotNull($member);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $actor->id,
            'action' => 'mission_team_member_assigned',
            'module' => 'missions',
        ]);

        $destroy = $this->actingAs($actor)->delete(route('missions.team-members.destroy', [$mission, $member]));

        $destroy->assertRedirect(route('missions.show', $mission));
        $this->assertDatabaseMissing('mission_team_members', ['id' => $member->id]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $actor->id,
            'action' => 'mission_team_member_removed',
            'module' => 'missions',
        ]);
    }

    public function test_chef_mission_demotes_previous_and_sets_auditeur(): void
    {
        [$dept, $actor, $auditeur, $mission] = $this->seedDeptMissionAndActor();

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

        $this->actingAs($actor)->post(route('missions.team-members.store', $mission), [
            'user_id' => $chef1->id,
            'mission_role' => MissionTeamMember::ROLE_CHEF_MISSION,
        ])->assertRedirect(route('missions.show', $mission));

        $mission->refresh();
        $this->assertSame($chef1->id, (int) $mission->auditeur_id);

        $this->actingAs($actor)->post(route('missions.team-members.store', $mission), [
            'user_id' => $chef2->id,
            'mission_role' => MissionTeamMember::ROLE_CHEF_MISSION,
        ])->assertRedirect(route('missions.show', $mission));

        $mission->refresh();
        $this->assertSame($chef2->id, (int) $mission->auditeur_id);

        $oldChefRow = MissionTeamMember::query()
            ->where('mission_id', $mission->id)
            ->where('user_id', $chef1->id)
            ->first();
        $this->assertSame(MissionTeamMember::ROLE_INSPECTEUR_VERIFICATEUR, $oldChefRow->mission_role);
    }
}
