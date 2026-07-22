<?php

namespace Tests\Feature\Missions;

use App\Models\Department;
use App\Models\Mission;
use App\Models\MissionTeamMember;
use App\Models\QuestionnaireTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissionAuditGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_supervisor_can_create_a_questionnaire_group_with_mission_members(): void
    {
        [$supervisor, $mission] = $this->missionManagedBySupervisor();
        $memberA = $this->addMember($mission);
        $memberB = $this->addMember($mission);
        $questionnaire = $this->questionnaire();

        $this->actingAs($supervisor)->post(route('missions.audit-groups.store', $mission), [
            'name' => 'Équipe A — Alignement stratégique',
            'questionnaire_template_id' => $questionnaire->id,
            'interviewed_person' => 'Chef du service Réseau',
            'interviewed_role' => 'Chef de service',
            'member_ids' => [$memberA->id, $memberB->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('mission_audit_groups', [
            'mission_id' => $mission->id,
            'name' => 'Équipe A — Alignement stratégique',
            'questionnaire_template_id' => $questionnaire->id,
        ]);
        $this->assertDatabaseCount('mission_audit_group_members', 2);
    }

    public function test_a_member_from_another_mission_cannot_be_assigned_to_the_group(): void
    {
        [$supervisor, $mission] = $this->missionManagedBySupervisor();
        $otherMission = Mission::query()->create([
            'organisation' => 'Autre audit',
            'date_debut' => Carbon::today(),
            'auditeur_id' => $supervisor->id,
            'department_id' => $mission->department_id,
            'mission_status' => Mission::STATUS_BROUILLON,
        ]);
        $externalMember = $this->addMember($otherMission);

        $this->actingAs($supervisor)->post(route('missions.audit-groups.store', $mission), [
            'name' => 'Équipe invalide',
            'questionnaire_template_id' => $this->questionnaire()->id,
            'member_ids' => [$externalMember->id],
        ])->assertSessionHasErrors('member_ids.0');

        $this->assertDatabaseCount('mission_audit_groups', 0);
    }

    /** @return array{User, Mission} */
    private function missionManagedBySupervisor(): array
    {
        $department = Department::query()->create([
            'name' => 'Pôle informatique',
            'code' => 'PI',
            'type' => 'pole',
            'active' => true,
        ]);
        $supervisor = User::factory()->create([
            'department_id' => $department->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);
        $department->update(['supervisor_user_id' => $supervisor->id]);
        $mission = Mission::query()->create([
            'organisation' => 'Audit du management de la DSI',
            'date_debut' => Carbon::today(),
            'auditeur_id' => $supervisor->id,
            'department_id' => $department->id,
            'mission_status' => Mission::STATUS_BROUILLON,
        ]);

        return [$supervisor, $mission];
    }

    private function addMember(Mission $mission): MissionTeamMember
    {
        $user = User::factory()->create([
            'department_id' => $mission->department_id,
            'approval_status' => 'approved',
            'active' => true,
        ]);

        return MissionTeamMember::query()->create([
            'mission_id' => $mission->id,
            'user_id' => $user->id,
            'mission_role' => MissionTeamMember::ROLE_INSPECTEUR_VERIFICATEUR,
            'assigned_at' => now(),
        ]);
    }

    private function questionnaire(): QuestionnaireTemplate
    {
        return QuestionnaireTemplate::query()->create([
            'name' => 'Alignement stratégique ISACA / COBIT',
            'slug' => 'alignement-strategique-isaca-cobit',
            'active' => true,
            'lifecycle_status' => QuestionnaireTemplate::STATUS_PUBLISHED,
            'is_global_template' => true,
        ]);
    }
}
