<?php

namespace Tests\Feature;

use App\Models\AdministrativeTask;
use App\Models\CorrespondenceMovement;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministrativeModulesAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_controls_individual_module_visibility(): void
    {
        $superAdmin = $this->superAdmin();
        $user = User::factory()->create([
            'active' => true,
            'approval_status' => User::APPROVAL_STATUS_APPROVED,
            'gec_menu_enabled' => false,
            'administrative_work_menu_enabled' => false,
        ]);

        $this->assertTrue($superAdmin->can('accessCorrespondence'));
        $this->assertTrue($superAdmin->can('accessAdministrativeWork'));
        $this->assertFalse($user->can('accessCorrespondence'));
        $this->assertFalse($user->can('accessAdministrativeWork'));

        $user->update([
            'gec_menu_enabled' => true,
            'administrative_work_menu_enabled' => true,
        ]);

        $this->assertTrue($user->fresh()->can('accessCorrespondence'));
        $this->assertTrue($user->fresh()->can('accessAdministrativeWork'));
    }

    public function test_authorized_user_registers_correspondence_and_creates_linked_task(): void
    {
        $department = Department::query()->create([
            'name' => 'Direction administrative',
            'code' => 'DA',
            'type' => 'direction',
            'active' => true,
        ]);
        $user = User::factory()->create([
            'active' => true,
            'approval_status' => User::APPROVAL_STATUS_APPROVED,
            'department_id' => $department->id,
            'gec_menu_enabled' => true,
            'administrative_work_menu_enabled' => true,
        ]);

        $response = $this->actingAs($user)->post(route('correspondence.store'), [
            'direction' => 'incoming',
            'sender' => 'Ministère du Budget',
            'subject' => 'Demande de situation',
            'confidentiality' => 'normal',
            'urgency' => 'urgent',
            'received_at' => now()->format('Y-m-d H:i:s'),
            'current_department_id' => $department->id,
            'current_assignee_id' => $user->id,
        ]);

        $record = CorrespondenceRecord::query()->firstOrFail();
        $response->assertRedirect(route('correspondence.show', $record));
        $this->assertStringStartsWith('DGCPT-CR-', $record->reference);
        $this->assertSame('assigned', $record->status);
        $this->assertDatabaseCount('correspondence_movements', 1);
        $this->assertSame('registered', CorrespondenceMovement::query()->firstOrFail()->event_type);

        $taskResponse = $this->actingAs($user)->post(route('administrative-work.store'), [
            'correspondence_record_id' => $record->id,
            'title' => 'Préparer la réponse',
            'description' => 'Produire une situation vérifiée.',
            'priority' => 'high',
            'department_id' => $department->id,
            'owner_id' => $user->id,
            'assignee_id' => $user->id,
            'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $task = AdministrativeTask::query()->firstOrFail();
        $taskResponse->assertRedirect(route('administrative-work.show', $task));
        $this->assertSame($record->id, $task->correspondence_record_id);
        $this->assertSame('assigned', $task->status);
    }

    private function superAdmin(): User
    {
        $role = Role::query()->create([
            'slug' => 'super_admin',
            'name' => 'Super administrateur',
            'hierarchy_level' => 100,
            'active' => true,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'role' => 'admin',
            'active' => true,
            'approval_status' => User::APPROVAL_STATUS_APPROVED,
        ]);
    }
}
