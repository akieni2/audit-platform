<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Mission;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissionWorkflowNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_inspector_validation_notifies_auditeur(): void
    {
        $dept = Department::query()->create([
            'name' => 'Pôle Test',
            'code' => 'TEST',
            'type' => 'pole',
            'description' => 'Test',
            'active' => true,
        ]);

        $roleAuditeur = Role::query()->create([
            'slug' => 'charge_verification',
            'name' => 'Chargé',
            'hierarchy_level' => 20,
            'active' => true,
        ]);

        $roleInspecteur = Role::query()->create([
            'slug' => 'inspecteur_services',
            'name' => 'IS',
            'hierarchy_level' => 100,
            'active' => true,
        ]);

        $auditeur = User::factory()->create([
            'department_id' => $dept->id,
            'role_id' => $roleAuditeur->id,
        ]);

        $inspecteur = User::factory()->create([
            'department_id' => $dept->id,
            'role_id' => $roleInspecteur->id,
        ]);

        $mission = Mission::query()->create([
            'organisation' => 'Org test',
            'description' => 'Desc',
            'date_debut' => Carbon::today(),
            'date_fin' => null,
            'auditeur_id' => $auditeur->id,
            'department_id' => $dept->id,
            'mission_status' => Mission::STATUS_CLOTUREE,
        ]);

        $response = $this->actingAs($inspecteur)->post(route('missions.workflow', $mission), [
            'action' => 'valider_is',
            'comment' => '',
        ]);

        $response->assertRedirect(route('missions.show', $mission));

        $auditeur->refresh();
        $this->assertSame(1, $auditeur->unreadNotifications()->count());

        $data = $auditeur->notifications()->first()->data;
        $this->assertArrayHasKey('mission_id', $data);
        $this->assertSame($mission->id, $data['mission_id']);
        $this->assertStringContainsString('Validation Inspection des Services', (string) ($data['title'] ?? ''));
    }
}
