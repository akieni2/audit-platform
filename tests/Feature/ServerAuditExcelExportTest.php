<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Mission;
use App\Models\MissionTeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class ServerAuditExcelExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_mission_member_can_download_power_query_ready_excel_export(): void
    {
        $department = Department::query()->create(['name' => 'Audit', 'code' => 'AUD', 'type' => 'pole', 'active' => true]);
        $user = User::factory()->create(['department_id' => $department->id]);
        $mission = Mission::query()->create([
            'organisation' => 'Laboratoire Data Analytics', 'reference' => 'DA-J1-001',
            'date_debut' => now()->toDateString(), 'auditeur_id' => $user->id,
            'department_id' => $department->id, 'mission_type' => 'audit_si',
        ]);
        MissionTeamMember::query()->create([
            'mission_id' => $mission->id, 'user_id' => $user->id,
            'mission_role' => MissionTeamMember::ROLE_CHEF_MISSION, 'is_lead' => true, 'assigned_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('missions.audit-data-analytics.export', $mission));
        $response->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $path = storage_path('app/export-test.xlsx');
        file_put_contents($path, $response->getContent());
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $workbook = $zip->getFromName('xl/workbook.xml');
        $this->assertStringContainsString('Grille_Constats', $workbook);
        $this->assertStringContainsString('Resultats_Techniques', $workbook);
        $this->assertStringContainsString('Referentiels', $workbook);
        $this->assertStringContainsString('Power_Query', $workbook);
        $zip->close();
        unlink($path);
    }

    public function test_user_outside_mission_cannot_export(): void
    {
        $department = Department::query()->create(['name' => 'Audit', 'code' => 'AUD2', 'type' => 'pole', 'active' => true]);
        $owner = User::factory()->create(['department_id' => $department->id]);
        $outsider = User::factory()->create();
        $mission = Mission::query()->create([
            'organisation' => 'Mission confidentielle', 'date_debut' => now()->toDateString(),
            'auditeur_id' => $owner->id, 'department_id' => $department->id, 'mission_type' => 'audit_si',
        ]);

        $this->actingAs($outsider)->get(route('missions.audit-data-analytics.export', $mission))->assertNotFound();
    }
}
