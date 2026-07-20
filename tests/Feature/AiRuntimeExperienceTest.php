<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsAiCopilotContext;
use Tests\TestCase;

class AiRuntimeExperienceTest extends TestCase
{
    use BuildsAiCopilotContext;
    use RefreshDatabase;

    public function test_copilot_pages_render_for_mission(): void
    {
        $department = $this->hardeningDepartment();
        $user = $this->hardeningInspectorUser($department);
        $this->ensureAiTenant($department);
        $mission = $this->createMission($user, $department);

        $this->actingAs($user)
            ->get(route('ai.mission', $mission))
            ->assertOk()
            ->assertSee('Copilote d’audit et des risques')
            ->assertSee('Validation humaine');

        $this->actingAs($user)
            ->get(route('ai.assistant', $mission))
            ->assertOk()
            ->assertSee('Assistant mission');
    }
}
