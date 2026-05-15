<?php

namespace Tests\Feature;

use App\Services\Ai\Control\InternalControlAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsAiCopilotContext;
use Tests\TestCase;

class InternalControlAiTest extends TestCase
{
    use BuildsAiCopilotContext;
    use RefreshDatabase;

    public function test_internal_control_analysis_returns_compliance_gaps(): void
    {
        $department = $this->hardeningDepartment('CTL');
        $user = $this->hardeningInspectorUser($department);
        $this->ensureAiTenant($department);
        $this->bindTenantFor($user);
        $mission = $this->createMission($user, $department);

        $result = app(InternalControlAiService::class)->analyze($mission, $user, 'ISO27001');

        $this->assertArrayHasKey('compliance', $result);
        $this->assertArrayHasKey('gaps', $result);
    }
}
