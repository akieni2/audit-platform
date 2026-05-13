<?php

namespace Tests\Unit;

use App\Domain\Risk\Enums\CriticalityLevel;
use App\Services\Risk\CriticalityEvaluationService;
use PHPUnit\Framework\TestCase;

class CriticalityEvaluationServiceTest extends TestCase
{
    private CriticalityEvaluationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CriticalityEvaluationService;
    }

    public function test_levels_follow_score_thresholds(): void
    {
        $this->assertSame(CriticalityLevel::Low, $this->service->levelFromScore(1));
        $this->assertSame(CriticalityLevel::Low, $this->service->levelFromScore(6));
        $this->assertSame(CriticalityLevel::Medium, $this->service->levelFromScore(7));
        $this->assertSame(CriticalityLevel::Medium, $this->service->levelFromScore(12));
        $this->assertSame(CriticalityLevel::High, $this->service->levelFromScore(13));
        $this->assertSame(CriticalityLevel::High, $this->service->levelFromScore(18));
        $this->assertSame(CriticalityLevel::Critical, $this->service->levelFromScore(19));
        $this->assertSame(CriticalityLevel::Critical, $this->service->levelFromScore(25));
    }

    public function test_heatmap_tint_matches_levels(): void
    {
        $this->assertSame('green', $this->service->heatmapTintForCoordinates(1, 2));
        $this->assertSame('yellow', $this->service->heatmapTintForCoordinates(2, 4));
        $this->assertSame('orange', $this->service->heatmapTintForCoordinates(3, 5));
        $this->assertSame('red', $this->service->heatmapTintForCoordinates(5, 5));
    }
}
