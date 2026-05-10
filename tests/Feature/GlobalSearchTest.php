<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Mission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_requires_authentication(): void
    {
        $this->get(route('search', ['q' => 'ab']))->assertRedirect();
    }

    public function test_search_returns_matching_visible_mission(): void
    {
        $dept = Department::query()->create([
            'name' => 'Pôle X',
            'code' => 'POLEX',
            'type' => 'pole',
            'description' => 'Test',
            'active' => true,
        ]);

        $user = User::factory()->create(['department_id' => $dept->id]);

        Mission::query()->create([
            'organisation' => 'Organisation secrète TESTXYZ',
            'description' => 'd',
            'date_debut' => Carbon::today(),
            'date_fin' => null,
            'auditeur_id' => $user->id,
            'department_id' => $dept->id,
        ]);

        $response = $this->actingAs($user)->get(route('search', ['q' => 'TESTXYZ']));

        $response->assertOk();
        $response->assertSee('Organisation secrète TESTXYZ', false);
    }
}
