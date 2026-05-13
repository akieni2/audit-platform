<?php

namespace App\Services\Risk;

use App\Domain\Risk\Enums\RiskLifecycleStatus;
use App\Models\IdentifiedRisk;
use DomainException;

final class RiskLifecycleGuardService
{
    public function ensureCanReview(IdentifiedRisk $identifiedRisk): void
    {
        $this->ensureTransitionAllowed(
            $identifiedRisk,
            [
                RiskLifecycleStatus::Detected,
                RiskLifecycleStatus::Reviewed,
                RiskLifecycleStatus::Qualified,
            ],
            'review',
        );
    }

    public function ensureCanApprove(IdentifiedRisk $identifiedRisk): void
    {
        $this->ensureTransitionAllowed(
            $identifiedRisk,
            [
                RiskLifecycleStatus::Reviewed,
                RiskLifecycleStatus::Qualified,
                RiskLifecycleStatus::Approved,
            ],
            'approve',
        );
    }

    public function ensureCanPromote(IdentifiedRisk $identifiedRisk): void
    {
        $this->ensureTransitionAllowed(
            $identifiedRisk,
            [
                RiskLifecycleStatus::Detected,
                RiskLifecycleStatus::Reviewed,
                RiskLifecycleStatus::Qualified,
                RiskLifecycleStatus::Approved,
                RiskLifecycleStatus::Promoted,
            ],
            'promote',
        );
    }

    /**
     * @param  list<RiskLifecycleStatus>  $allowed
     */
    private function ensureTransitionAllowed(IdentifiedRisk $identifiedRisk, array $allowed, string $operation): void
    {
        $current = RiskLifecycleStatus::tryFrom((string) ($identifiedRisk->lifecycle_status ?: RiskLifecycleStatus::Detected->value))
            ?? RiskLifecycleStatus::Detected;

        if (! in_array($current, $allowed, true)) {
            throw new DomainException(sprintf(
                'Transition lifecycle invalide pour %s depuis l’état "%s".',
                $operation,
                $current->value
            ));
        }
    }
}
