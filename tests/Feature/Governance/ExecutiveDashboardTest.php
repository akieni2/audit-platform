<?php

namespace Tests\Feature\Governance;

use App\Models\User;
use Database\Seeders\DgcptFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutiveDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_executive_dashboard(): void
    {
        $this->get(route('dashboard.executive'))
            ->assertRedirect();
    }

    public function test_inspecteur_demo_can_view_executive_dashboard(): void
    {
        $this->seed(DgcptFoundationSeeder::class);

        $user = User::query()->where('email', 'inspecteur.dgcpt@example.gov')->firstOrFail();

        $this->actingAs($user)
            ->get(route('dashboard.executive'))
            ->assertOk();
    }

    public function test_standard_user_without_supervise_cannot_view_executive_dashboard(): void
    {
        $this->seed(DgcptFoundationSeeder::class);

        $user = User::factory()->create([
            'role' => 'auditeur',
            'role_id' => null,
            'department_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.executive'))
            ->assertForbidden();
    }
}
