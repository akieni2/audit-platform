<?php

namespace Tests\Unit;

use App\Services\Risk\RiskScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_packages_inherent_scores_from_free_text_inputs(): void
    {
        $service = app(RiskScoringService::class);

        $package = $service->packageInherent('élevée', 'moyenne', 'critique');

        $this->assertSame(4, $package['probability']);
        $this->assertSame(3, $package['impact']);
        $this->assertSame(12, $package['score']);
        $this->assertSame('medium', $package['criticality']);
    }

    public function test_it_builds_residual_scores_from_control_coefficient(): void
    {
        $service = app(RiskScoringService::class);

        $package = $service->packageResidualFromCoefficient(5, 5, 0.2);

        $this->assertSame(5, $package['impact']);
        $this->assertSame(1, $package['probability']);
        $this->assertSame(5, $package['score']);
        $this->assertSame('low', $package['criticality']);
    }
}
