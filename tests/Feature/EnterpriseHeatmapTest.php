<?php

namespace Tests\Feature;

use App\Models\IdentifiedRisk;
use App\Services\Risk\EnterpriseHeatmapService;
use App\Services\Risk\MissionRiskDashboardService;
use App\Services\Risk\RiskRegistryPromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsRiskRegistryContext;
use Tests\TestCase;

class EnterpriseHeatmapTest extends TestCase
{
    use BuildsRiskRegistryContext;
    use RefreshDatabase;

    public function test_enterprise_heatmap_combines_registry_and_intake_sources(): void
    {
        $context = $this->createRiskRegistryContext();

        $promoted = app(RiskRegistryPromotionService::class)->promote(
            $context['identifiedRisk'],
            $context['user'],
            'Promotion enterprise'
        );

        IdentifiedRisk::query()->create([
            'mission_id' => $context['mission']->id,
            'service_id' => $context['service']->id,
            'title' => 'Risque encore détecté',
            'source_signature' => sha1('detected-risk'),
            'criticality' => 'medium',
            'probability' => '3',
            'impact' => '3',
        ]);

        $snapshot = app(EnterpriseHeatmapService::class)->mission($context['mission']->id);

        $this->assertGreaterThanOrEqual(2, array_sum($snapshot['combined']['counts']));
        $this->assertGreaterThan(array_sum($snapshot['registry']['counts']), array_sum($snapshot['combined']['counts']));
        $this->assertSame(1, $snapshot['registry']['counts'][$promoted->heatmap_x.'-'.$promoted->heatmap_y] ?? 0);
    }

    public function test_mission_dashboard_exposes_enterprise_kpis(): void
    {
        $context = $this->createRiskRegistryContext();
        app(RiskRegistryPromotionService::class)->promote($context['identifiedRisk'], $context['user'], 'Promotion enterprise');

        $snapshot = app(MissionRiskDashboardService::class)->snapshot($context['mission']->id);

        $this->assertArrayHasKey('critical_open', $snapshot);
        $this->assertArrayHasKey('top_services', $snapshot);
        $this->assertArrayHasKey('lifecycle', $snapshot);
        $this->assertArrayHasKey('heatmap', $snapshot);
        $this->assertSame(1, $snapshot['promoted']);
        $this->assertGreaterThanOrEqual(1, $snapshot['critical_open']);
    }
}
