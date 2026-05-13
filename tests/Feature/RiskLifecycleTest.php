<?php

namespace Tests\Feature;

use App\Services\Risk\RiskRegistryPromotionService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsRiskRegistryContext;
use Tests\TestCase;

class RiskLifecycleTest extends TestCase
{
    use BuildsRiskRegistryContext;
    use RefreshDatabase;

    public function test_review_validation_and_registry_closure_flow_updates_canonical_statuses(): void
    {
        $context = $this->createRiskRegistryContext();
        $service = app(RiskRegistryPromotionService::class);

        $reviewed = $service->submitForReview($context['identifiedRisk'], $context['user'], 'review');
        $this->assertSame('under_review', $reviewed->lifecycle_status);
        $this->assertNotNull($reviewed->reviewed_at);

        $approved = $service->approve($reviewed->fresh(), $context['user'], 'approve');
        $this->assertSame('validated', $approved->lifecycle_status);
        $this->assertTrue((bool) $approved->validated_by_human);

        $register = $service->promote($approved->fresh(), $context['user'], 'promote');
        $this->assertSame('promoted', $register->lifecycle_status);
        $this->assertSame('identifie', $register->statut_risque);

        $mitigated = $service->mitigate($register->fresh(), $context['user'], 'mitigate');
        $this->assertSame('mitigated', $mitigated->lifecycle_status);

        $closed = $service->close($mitigated->fresh(), $context['user'], 'close');
        $this->assertSame('closed', $closed->lifecycle_status);
        $this->assertNotNull($closed->closed_at);

        $archived = $service->archive($closed->fresh(), $context['user'], 'archive');
        $this->assertSame('archived', $archived->lifecycle_status);
        $this->assertNotNull($archived->archived_at);
    }

    public function test_rejected_identified_risk_cannot_be_promoted(): void
    {
        $context = $this->createRiskRegistryContext();
        $service = app(RiskRegistryPromotionService::class);

        $rejected = $service->reject($context['identifiedRisk'], $context['user'], 'invalid');

        $this->expectException(DomainException::class);

        $service->promote($rejected->fresh(), $context['user'], 'forbidden');
    }
}
