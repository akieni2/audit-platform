<?php

namespace Tests\Feature;

use App\Services\Ai\AiResponseSanitizerService;
use App\Services\Ai\Governance\AiSafetyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_sanitizer_appends_human_validation_notice(): void
    {
        $out = app(AiResponseSanitizerService::class)->sanitize('Suggestion simple.');
        $this->assertStringContainsString('Validation humaine', $out);
    }

    public function test_safety_service_flags_hallucination_risk(): void
    {
        $result = app(AiSafetyService::class)->validateResponse('Je garantis un résultat certain.');
        $this->assertTrue($result['hallucination_risk']);
    }
}
