<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\InstitutionalProcess;
use App\Models\ProcessDomain;
use App\Models\ProcessModuleAccess;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionalProcessModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_is_granted_by_structure_and_inherited_by_children(): void
    {
        $admin = $this->superAdmin();
        $direction = $this->department('DSI', 'Direction SI');
        $service = $this->department('DEV', 'Service développement', $direction->id);
        $outsiderDepartment = $this->department('RH', 'Ressources humaines');
        $member = User::factory()->create(['department_id' => $service->id, 'active' => true, 'approval_status' => User::APPROVAL_STATUS_APPROVED]);
        $outsider = User::factory()->create(['department_id' => $outsiderDepartment->id, 'active' => true, 'approval_status' => User::APPROVAL_STATUS_APPROVED]);
        $this->actingAs($admin)->put(route('institutional-processes.access.update', $direction), ['active' => 1, 'default_role' => 'contributor', 'inherit_to_children' => 1])->assertRedirect();
        $this->assertTrue($member->fresh()->canAccessInstitutionalProcesses());
        $this->assertFalse($outsider->fresh()->canAccessInstitutionalProcesses());
        $this->actingAs($member)->get(route('institutional-processes.index'))->assertOk();
        $this->actingAs($outsider)->get(route('institutional-processes.index'))->assertForbidden();
        ProcessModuleAccess::query()->create(['department_id' => $service->id, 'default_role' => 'viewer', 'inherit_to_children' => true, 'active' => false, 'granted_by' => $admin->id]);
        $this->assertFalse($member->fresh()->canAccessInstitutionalProcesses());
    }

    public function test_contributor_creates_transversal_process_and_documents_its_flow(): void
    {
        $admin = $this->superAdmin();
        $ownerDepartment = $this->department('PI', 'Pôle informatique');
        $participant = $this->department('IS', 'Inspection des services');
        $user = User::factory()->create(['department_id' => $ownerDepartment->id, 'active' => true, 'approval_status' => User::APPROVAL_STATUS_APPROVED]);
        ProcessModuleAccess::query()->create(['department_id' => $ownerDepartment->id, 'default_role' => 'contributor', 'inherit_to_children' => true, 'active' => true, 'granted_by' => $admin->id]);
        $domain = ProcessDomain::query()->create(['owner_department_id' => $ownerDepartment->id, 'code' => 'INFRA', 'name' => 'Infrastructure', 'active' => true, 'created_by' => $admin->id]);
        $response = $this->actingAs($user)->post(route('institutional-processes.store'), ['domain_id' => $domain->id, 'owner_department_id' => $ownerDepartment->id, 'owner_user_id' => $user->id, 'code' => 'PROC-IT-001', 'name' => 'Gestion des incidents', 'objective' => 'Rétablir les services', 'criticality' => 'high', 'priority' => 'high', 'visibility' => 'participants', 'participant_ids' => [$participant->id]]);
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $process = InstitutionalProcess::query()->firstOrFail();
        $response->assertRedirect(route('institutional-processes.show', $process));
        $this->assertDatabaseHas('institutional_process_department', ['institutional_process_id' => $process->id, 'department_id' => $participant->id]);
        $this->actingAs($user)->post(route('institutional-processes.activities.store', $process), ['sequence' => 1, 'title' => 'Qualification'])->assertRedirect();
        $this->actingAs($user)->post(route('institutional-processes.elements.store', $process), ['type' => 'input', 'name' => 'Ticket utilisateur'])->assertRedirect();
        $this->actingAs($user)->post(route('institutional-processes.kpis.store', $process), ['name' => 'Temps moyen de résolution', 'unit' => 'minutes', 'target_value' => 60])->assertRedirect();
        $this->assertDatabaseCount('process_activities', 1);
        $this->assertDatabaseCount('process_elements', 1);
        $this->assertDatabaseCount('process_kpis', 1);
        $this->assertDatabaseCount('process_history', 4);
    }

    private function department(string $code, string $name, ?int $parent = null): Department
    {
        return Department::query()->create(['name' => $name, 'code' => $code, 'type' => $parent ? 'service' : 'direction', 'active' => true, 'parent_department_id' => $parent]);
    }

    private function superAdmin(): User
    {
        $role = Role::query()->create(['slug' => 'super_admin', 'name' => 'Super administrateur', 'hierarchy_level' => 100, 'active' => true]);

        return User::factory()->create(['role_id' => $role->id, 'role' => 'admin', 'active' => true, 'approval_status' => User::APPROVAL_STATUS_APPROVED]);
    }
}
