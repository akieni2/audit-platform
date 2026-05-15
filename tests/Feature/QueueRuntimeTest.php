<?php

namespace Tests\Feature;

use App\Services\Runtime\QueueHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueRuntimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_health_snapshot_returns_structure(): void
    {
        $snapshot = app(QueueHealthService::class)->snapshot();

        $this->assertArrayHasKey('pending_jobs', $snapshot);
        $this->assertArrayHasKey('failed_jobs', $snapshot);
        $this->assertArrayHasKey('projection_queue', $snapshot);
    }

    public function test_enterprise_queue_monitoring_page_renders(): void
    {
        $department = \App\Models\Department::query()->create([
            'name' => 'Queue Dept',
            'code' => 'QUE',
            'type' => 'pole',
            'active' => true,
        ]);
        $role = \App\Models\Role::query()->create([
            'slug' => 'administrateur_institutionnel',
            'name' => 'admin',
            'hierarchy_level' => 900,
            'active' => true,
        ]);
        $user = \App\Models\User::factory()->create([
            'department_id' => $department->id,
            'role_id' => $role->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('observability.enterprise.queues'))
            ->assertOk()
            ->assertSee('Queue Monitoring');
    }
}
