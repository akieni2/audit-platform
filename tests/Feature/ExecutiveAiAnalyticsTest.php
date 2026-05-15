<?php

namespace Tests\Feature;

use App\Services\Ai\Executive\ExecutiveAiAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsAiCopilotContext;
use Tests\TestCase;

class ExecutiveAiAnalyticsTest extends TestCase
{
    use BuildsAiCopilotContext;
    use RefreshDatabase;

    public function test_executive_analytics_returns_insights_bundle(): void
    {
        $department = $this->hardeningDepartment('EXE');
        $user = $this->hardeningAdminUser($department);
        $this->ensureAiTenant($department);
        $this->bindTenantFor($user);
        $this->createMission($user, $department);

        $bundle = app(ExecutiveAiAnalyticsService::class)->dashboardInsights($department, $user);

        $this->assertArrayHasKey('predictive', $bundle);
        $this->assertArrayHasKey('narrative', $bundle);
        $this->assertArrayHasKey('strategic', $bundle);
    }
}
