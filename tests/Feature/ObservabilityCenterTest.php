<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsWorkflowRuntimeContext;
use Tests\TestCase;

class ObservabilityCenterTest extends TestCase
{
    use BuildsWorkflowRuntimeContext;
    use RefreshDatabase;

    public function test_observability_center_renders_runtime_health_queues_and_projection_cards(): void
    {
        $department = $this->createDepartment('OBS');
        $user = $this->createUser('inspecteur_services', $department);

        $this->actingAs($user)
            ->get(route('workflow-runtime.observability'))
            ->assertOk()
            ->assertSee('Workflow observability center')
            ->assertSee('Queues')
            ->assertSee('Projections')
            ->assertSee('Metrics & événements');
    }
}
