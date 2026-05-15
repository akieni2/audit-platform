<?php

namespace Tests\Feature;

use App\Models\AiRecommendation;
use App\Services\Ai\Governance\AiExplainabilityService;
use App\Services\Ai\Governance\AiGovernanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsAiCopilotContext;
use Tests\TestCase;

class AiGovernanceTest extends TestCase
{
    use BuildsAiCopilotContext;
    use RefreshDatabase;

    public function test_auto_execute_is_forbidden_by_policy(): void
    {
        config(['ai_copilot.auto_execute_recommendations' => true]);

        $department = $this->hardeningDepartment();
        $user = $this->hardeningInspectorUser($department);
        $mission = $this->createMission($user, $department);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        app(AiGovernanceService::class)->assertAssistiveRequestAllowed($user, $mission);
    }

    public function test_explainability_marks_human_as_source_of_truth(): void
    {
        $department = $this->hardeningDepartment();
        $user = $this->hardeningInspectorUser($department);
        $this->ensureAiTenant($department);
        $this->bindTenantFor($user);
        $mission = $this->createMission($user, $department);

        $rec = AiRecommendation::query()->create([
            'mission_id' => $mission->id,
            'user_id' => $user->id,
            'recommendation_type' => 'general',
            'confidence_level' => 'medium',
            'title' => 'Test',
            'summary' => 'Assistive',
            'requires_human_validation' => true,
        ]);

        $explain = app(AiExplainabilityService::class)->explain($rec);
        $this->assertSame('human_auditor', $explain['source_of_truth']);
    }
}
