<?php

namespace Tests\Feature;

use App\Services\Resilience\ProjectionRecoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsEnterpriseHardeningContext;
use Tests\TestCase;

class RuntimeRecoveryTest extends TestCase
{
    use BuildsEnterpriseHardeningContext;
    use RefreshDatabase;

    public function test_projection_recovery_detects_orphans(): void
    {
        $department = $this->hardeningDepartment();
        $user = $this->hardeningInspectorUser($department);
        $mission = $this->createMission($user, $department);

        $orphans = app(ProjectionRecoveryService::class)->detectOrphans($mission);

        $this->assertContains('missing_workflow_instance', $orphans);
    }
}
