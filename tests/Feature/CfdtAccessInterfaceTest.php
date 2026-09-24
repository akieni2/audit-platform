<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CfdtAccessInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_explicit_cfdt_role_assignment_choices(): void
    {
        $superAdminRole = Role::query()->create([
            'slug' => 'super_admin',
            'name' => 'Super administrateur',
            'hierarchy_level' => 110,
            'active' => true,
        ]);
        $agentRole = Role::query()->create([
            'slug' => 'agent_operationnel',
            'name' => 'Agent opérationnel',
            'hierarchy_level' => 10,
            'active' => true,
        ]);
        $superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'active' => true,
            'approval_status' => User::APPROVAL_STATUS_APPROVED,
        ]);
        User::factory()->create([
            'role_id' => $agentRole->id,
            'name' => 'AGENT TEST',
            'cfdt_role' => null,
            'active' => true,
            'approval_status' => User::APPROVAL_STATUS_APPROVED,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('cfdt.access'))
            ->assertOk()
            ->assertSee('Affecter au CFDT')
            ->assertSee('Agent / apprenant')
            ->assertSee('Formateur / enseignant')
            ->assertSee('Validateur pédagogique')
            ->assertSee('Administrateur CFDT')
            ->assertSee('Confirmer l’affectation')
            ->assertSee('Compte Super Admin protégé');
    }
}
