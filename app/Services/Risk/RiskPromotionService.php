<?php

namespace App\Services\Risk;

use App\Models\IdentifiedRisk;
use App\Models\Risque;
use App\Models\User;

final class RiskPromotionService
{
    public function __construct(
        private RiskRegistryPromotionService $registry,
    ) {}

    public function markReviewed(IdentifiedRisk $identifiedRisk, ?User $actor = null, ?string $notes = null): IdentifiedRisk
    {
        return $this->registry->submitForReview($identifiedRisk, $actor, $notes);
    }

    public function approve(IdentifiedRisk $identifiedRisk, ?User $actor = null, ?string $notes = null): IdentifiedRisk
    {
        return $this->registry->approve($identifiedRisk, $actor, $notes);
    }

    public function promote(IdentifiedRisk $identifiedRisk, ?User $actor = null, ?string $notes = null): Risque
    {
        return $this->registry->promote($identifiedRisk, $actor, $notes);
    }
}
