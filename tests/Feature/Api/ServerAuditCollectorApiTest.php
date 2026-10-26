<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\IdentifiedRisk;
use App\Models\Mission;
use App\Models\MissionDocument;
use App\Models\MissionTeamMember;
use App\Models\ServerAuditRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Database\Seeders\ServerAuditControlCatalogSeeder;
use Tests\TestCase;

class ServerAuditCollectorApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_scoped_collector_token_ingests_proof_and_creates_provisional_risk(): void
    {
        Storage::fake('local');
        [$user, $mission] = $this->context();
        Sanctum::actingAs($user, ['audit-collector:submit']);

        $payload = $this->payload();
        $response = $this->postJson("/api/v1/missions/{$mission->id}/server-audit/runs", $payload);

        $response->assertCreated()->assertJsonPath('summary.fail', 1)->assertJsonCount(1, 'provisional_risk_ids');
        $this->assertDatabaseHas('server_audit_runs', ['run_uuid' => $payload['run_uuid'], 'mission_id' => $mission->id]);
        $this->assertDatabaseHas('server_audit_results', ['control_code' => 'LINUX-ROOT-SSH', 'status' => 'fail']);
        $this->assertDatabaseHas('constats', ['mission_id' => $mission->id, 'status' => 'draft']);
        $this->assertDatabaseCount('constat_evidences', 1);
        $risk = IdentifiedRisk::query()->firstOrFail();
        $this->assertFalse($risk->validated_by_human);
        $this->assertSame('detected', $risk->lifecycle_status);
        $document = MissionDocument::query()->firstOrFail();
        Storage::disk('local')->assertExists($document->path);
    }

    public function test_run_uuid_is_idempotent_and_rejected_on_replay(): void
    {
        Storage::fake('local');
        [$user, $mission] = $this->context();
        Sanctum::actingAs($user, ['audit-collector:submit']);
        $payload = $this->payload();

        $this->postJson("/api/v1/missions/{$mission->id}/server-audit/runs", $payload)->assertCreated();
        $this->postJson("/api/v1/missions/{$mission->id}/server-audit/runs", $payload)->assertUnprocessable();
        $this->assertSame(1, ServerAuditRun::query()->count());
    }

    public function test_generic_api_token_cannot_submit_collector_results(): void
    {
        [$user, $mission] = $this->context();
        Sanctum::actingAs($user, ['risks:read']);
        $this->postJson("/api/v1/missions/{$mission->id}/server-audit/runs", $this->payload())->assertForbidden();
    }

    public function test_control_must_match_asset_platform(): void
    {
        [$user, $mission] = $this->context();
        Sanctum::actingAs($user, ['audit-collector:submit']);
        $payload = $this->payload();
        $payload['asset']['platform'] = 'windows';
        $this->postJson("/api/v1/missions/{$mission->id}/server-audit/runs", $payload)->assertUnprocessable();
    }

    private function context(): array
    {
        $this->seed(ServerAuditControlCatalogSeeder::class);
        $department = Department::query()->create(['name' => 'Pôle Audit Serveurs', 'code' => 'PAS', 'type' => 'pole', 'active' => true]);
        $user = User::factory()->create(['department_id' => $department->id]);
        $mission = Mission::query()->create(['organisation' => 'Laboratoire serveurs', 'date_debut' => now()->toDateString(), 'auditeur_id' => $user->id, 'department_id' => $department->id, 'mission_type' => 'audit_si']);
        MissionTeamMember::query()->create(['mission_id' => $mission->id, 'user_id' => $user->id, 'mission_role' => MissionTeamMember::ROLE_CHEF_MISSION, 'is_lead' => true, 'assigned_at' => now()]);
        return [$user, $mission];
    }

    public function test_unknown_server_family_is_accepted_when_control_is_universal(): void
    {
        Storage::fake('local');
        [$user, $mission] = $this->context();
        Sanctum::actingAs($user, ['audit-collector:submit']);
        $payload = $this->payload();
        $payload['asset']['platform'] = 'aix';
        $payload['asset']['type'] = 'server';
        $payload['checks'][0]['code'] = 'NMAP-UNEXPECTED-PORT';

        $this->postJson("/api/v1/missions/{$mission->id}/server-audit/runs", $payload)->assertCreated();
        $this->assertDatabaseHas('server_audit_assets', ['platform' => 'aix']);
    }

    public function test_result_keeps_a_versioned_referential_snapshot(): void
    {
        Storage::fake('local');
        [$user, $mission] = $this->context();
        Sanctum::actingAs($user, ['audit-collector:submit']);
        $this->postJson("/api/v1/missions/{$mission->id}/server-audit/runs", $this->payload())->assertCreated();

        $result = \App\Models\ServerAuditResult::query()->firstOrFail();
        $this->assertSame('CIS', $result->metadata['control_snapshot']['references'][0]['authority']);
        $this->assertSame('5.2.6', $result->metadata['control_snapshot']['references'][0]['section']);
    }

    private function payload(): array
    {
        return [
            'run_uuid' => '740d2df8-52c8-46b4-b516-8bbbc0a4d424',
            'collector_version' => '1.0.0',
            'started_at' => now()->subMinute()->toIso8601String(),
            'completed_at' => now()->toIso8601String(),
            'asset' => ['uuid' => '13753fa3-dbc4-4e98-bd4c-6dcc841b02b8', 'hostname' => 'ubuntu-lab', 'ip_address' => '192.168.56.40', 'platform' => 'linux', 'operating_system' => 'Ubuntu 24.04', 'role' => 'MySQL'],
            'checks' => [[
                'code' => 'LINUX-ROOT-SSH', 'status' => 'fail', 'expected' => 'PermitRootLogin no',
                'observed' => 'PermitRootLogin=yes', 'raw_output' => "permitrootlogin yes\n",
            ]],
        ];
    }
}
