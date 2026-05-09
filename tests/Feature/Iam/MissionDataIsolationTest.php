<?php

namespace Tests\Feature\Iam;

use App\Models\Department;
use App\Models\Mission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissionDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_only_missions_for_their_department_or_supervision(): void
    {
        $deptA = Department::query()->create([
            'name' => 'Pôle A',
            'code' => 'POLEA',
            'type' => 'pole',
            'description' => 'Test',
            'active' => true,
        ]);
        $deptB = Department::query()->create([
            'name' => 'Pôle B',
            'code' => 'POLEB',
            'type' => 'pole',
            'description' => 'Test',
            'active' => true,
        ]);

        $auditeur = User::factory()->create();
        $missionOther = Mission::query()->create([
            'organisation' => 'Org B secret',
            'description' => 'd',
            'date_debut' => Carbon::today(),
            'date_fin' => null,
            'auditeur_id' => $auditeur->id,
            'department_id' => $deptB->id,
        ]);

        $userDeptA = User::factory()->create([
            'department_id' => $deptA->id,
            'role' => 'auditeur',
        ]);

        $response = $this->actingAs($userDeptA)->get(route('missions.index'));

        $response->assertOk();
        $response->assertDontSee('Org B secret');
        $this->assertDatabaseHas('missions', ['id' => $missionOther->id]);
    }
}
