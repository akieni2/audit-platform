<?php

namespace Tests\Feature;

use App\Services\Ai\Audit\AuditAiAssistantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsAiCopilotContext;
use Tests\TestCase;

class AiAuditAssistantTest extends TestCase
{
    use BuildsAiCopilotContext;
    use RefreshDatabase;

    public function test_audit_assistant_generates_mission_summary(): void
    {
        $department = $this->hardeningDepartment('AUD');
        $user = $this->hardeningInspectorUser($department);
        $this->ensureAiTenant($department);
        $this->bindTenantFor($user);
        $mission = $this->createMission($user, $department);

        $result = app(AuditAiAssistantService::class)->missionSummary($mission, $user);

        $this->assertArrayHasKey('response', $result);
        $this->assertStringContainsString('validation humaine', strtolower($result['response']));
    }
}
