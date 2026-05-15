<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsEnterpriseHardeningContext;
use Tests\TestCase;

class EnterpriseObservabilityTest extends TestCase
{
    use BuildsEnterpriseHardeningContext;
    use RefreshDatabase;

    public function test_enterprise_health_dashboard_renders(): void
    {
        $user = $this->hardeningAdminUser();

        $this->actingAs($user)
            ->get(route('observability.enterprise.health'))
            ->assertOk()
            ->assertSee('Santé plateforme');
    }

    public function test_observability_center_links_to_enterprise_views(): void
    {
        $department = $this->hardeningDepartment('OBS');
        $user = $this->hardeningInspectorUser($department);

        $this->actingAs($user)
            ->get(route('workflow-runtime.observability'))
            ->assertOk()
            ->assertSee('Enterprise health');
    }
}
