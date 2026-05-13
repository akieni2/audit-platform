<?php

namespace Tests\Feature;

use App\Services\Risk\RiskRegistryPromotionService;
use App\Services\Risk\RiskScoringEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Feature\Concerns\BuildsRiskRegistryContext;
use Tests\TestCase;

class RiskRegistryPromotionTest extends TestCase
{
    use BuildsRiskRegistryContext;
    use RefreshDatabase;

    public function test_it_promotes_identified_risk_into_enterprise_registry_idempotently(): void
    {
        $context = $this->createRiskRegistryContext();

        $service = app(RiskRegistryPromotionService::class);

        $first = $service->promote($context['identifiedRisk'], $context['user'], 'Promotion enterprise');
        $second = $service->promote($context['identifiedRisk']->fresh(), $context['user'], 'Promotion enterprise bis');

        $this->assertSame($first->id, $second->id);
        $this->assertNotNull($first->risk_uuid);
        $this->assertNotNull($first->risk_reference);
        $this->assertSame('promoted', $first->lifecycle_status);
        $this->assertSame('critical', $first->criticality);
        $this->assertSame($context['identifiedRisk']->id, $first->source_identified_risk_id);
        $this->assertSame($context['mission']->department_id, $first->owner_department_id);
        $this->assertSame(5, $first->heatmap_x);
        $this->assertSame(4, $first->heatmap_y);

        $this->assertDatabaseCount('risques', 1);
        $this->assertDatabaseHas('identified_risks', [
            'id' => $context['identifiedRisk']->id,
            'lifecycle_status' => 'promoted',
            'validated_by_human' => 1,
        ]);
    }

    public function test_legacy_direct_submission_is_routed_through_identified_risk_pipeline(): void
    {
        $context = $this->createRiskRegistryContext();
        $legacy = $this->createLegacyActifChain($context['mission']);

        $risque = app(RiskRegistryPromotionService::class)->ingestLegacySubmission([
            'actif_id' => $legacy['actif']->id,
            'description' => 'Entrée legacy module risques',
            'impact_inherent' => 4,
            'probabilite_inherent' => 4,
            'proprietaire' => 'Responsable legacy',
            'departement' => 'DGOV',
            'statut_risque' => 'identifie',
            'plan_mitigation' => 'Plan historique',
        ], $context['user']);

        $this->assertSame($legacy['actif']->id, $risque->actif_id);
        $this->assertNotNull($risque->source_identified_risk_id);
        $this->assertDatabaseHas('identified_risks', [
            'id' => $risque->source_identified_risk_id,
            'category' => 'legacy_risk_module',
            'lifecycle_status' => 'promoted',
        ]);
    }

    public function test_transaction_rolls_back_if_scoring_engine_fails_during_promotion(): void
    {
        $context = $this->createRiskRegistryContext();

        $this->app->bind(RiskScoringEngine::class, function () {
            return new class extends RiskScoringEngine
            {
                public function __construct() {}

                public function inherent(int|string|null $probability, int|string|null $impact, ?string $criticality = null): array
                {
                    throw new RuntimeException('scoring failure');
                }
            };
        });

        try {
            app(RiskRegistryPromotionService::class)->promote($context['identifiedRisk'], $context['user'], 'boom');
            $this->fail('Promotion should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertSame('scoring failure', $exception->getMessage());
        }

        $this->assertDatabaseCount('risques', 0);
        $this->assertDatabaseHas('identified_risks', [
            'id' => $context['identifiedRisk']->id,
            'lifecycle_status' => 'detected',
        ]);
    }
}
