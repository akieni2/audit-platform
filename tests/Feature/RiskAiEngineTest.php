<?php

namespace Tests\Feature;

use App\Models\AiRecommendation;
use App\Services\Ai\Risk\RiskAiEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsAiCopilotContext;
use Tests\TestCase;

class RiskAiEngineTest extends TestCase
{
    use BuildsAiCopilotContext;
    use RefreshDatabase;

    public function test_risk_engine_persists_suggestions(): void
    {
        $department = $this->hardeningDepartment('RSK');
        $user = $this->hardeningInspectorUser($department);
        $this->ensureAiTenant($department);
        $this->bindTenantFor($user);
        $mission = $this->createMission($user, $department);

        app(RiskAiEngineService::class)->analyzeMission($mission, $user);

        $this->assertGreaterThan(0, AiRecommendation::query()->where('mission_id', $mission->id)->count());
    }
}
