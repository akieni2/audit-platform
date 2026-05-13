<?php

namespace App\Services\Risk;

use App\Domain\Risk\Enums\RiskLifecycleStatus;
use App\Models\IdentifiedRisk;
use App\Models\Risque;
use DomainException;

final class RiskLifecycleGuardService
{
    public function ensureCanSubmitForReview(IdentifiedRisk $identifiedRisk): void
    {
        $this->ensureTransitionAllowed(
            current: $identifiedRisk->lifecycle_status,
            allowed: [
                RiskLifecycleStatus::Detected,
                RiskLifecycleStatus::UnderReview,
                RiskLifecycleStatus::Validated,
            ],
            operation: 'submit_for_review',
        );
    }

    public function ensureCanReview(IdentifiedRisk $identifiedRisk): void
    {
        $this->ensureCanSubmitForReview($identifiedRisk);
    }

    public function ensureCanApprove(IdentifiedRisk $identifiedRisk): void
    {
        $this->ensureTransitionAllowed(
            current: $identifiedRisk->lifecycle_status,
            allowed: [
                RiskLifecycleStatus::Detected,
                RiskLifecycleStatus::UnderReview,
                RiskLifecycleStatus::Validated,
            ],
            operation: 'approve',
        );
    }

    public function ensureCanReject(IdentifiedRisk $identifiedRisk): void
    {
        $this->ensureTransitionAllowed(
            current: $identifiedRisk->lifecycle_status,
            allowed: [
                RiskLifecycleStatus::Detected,
                RiskLifecycleStatus::UnderReview,
                RiskLifecycleStatus::Validated,
            ],
            operation: 'reject',
        );
    }

    public function ensureCanPromote(IdentifiedRisk $identifiedRisk): void
    {
        $this->ensureTransitionAllowed(
            current: $identifiedRisk->lifecycle_status,
            allowed: [
                RiskLifecycleStatus::Detected,
                RiskLifecycleStatus::UnderReview,
                RiskLifecycleStatus::Validated,
                RiskLifecycleStatus::Promoted,
            ],
            operation: 'promote',
        );
    }

    /**
     * Official risk register lifecycle controls.
     */
    public function ensureCanMitigate(Risque $risque): void
    {
        $this->ensureTransitionAllowed(
            current: $risque->lifecycle_status,
            allowed: [
                RiskLifecycleStatus::Promoted,
                RiskLifecycleStatus::Mitigated,
            ],
            operation: 'mitigate',
        );
    }

    public function ensureCanClose(Risque $risque): void
    {
        $this->ensureTransitionAllowed(
            current: $risque->lifecycle_status,
            allowed: [
                RiskLifecycleStatus::Promoted,
                RiskLifecycleStatus::Mitigated,
                RiskLifecycleStatus::Closed,
            ],
            operation: 'close',
        );
    }

    public function ensureCanArchive(Risque $risque): void
    {
        $this->ensureTransitionAllowed(
            current: $risque->lifecycle_status,
            allowed: [
                RiskLifecycleStatus::Closed,
                RiskLifecycleStatus::Rejected,
                RiskLifecycleStatus::Archived,
            ],
            operation: 'archive',
        );
    }

    /**
     * @param  list<RiskLifecycleStatus>  $allowed
     */
    private function ensureTransitionAllowed(?string $current, array $allowed, string $operation): void
    {
        $currentState = RiskLifecycleStatus::fromMixed($current);

        if (! in_array($currentState, $allowed, true)) {
            throw new DomainException(sprintf(
                'Transition lifecycle invalide pour %s depuis l’état "%s".',
                $operation,
                $currentState->value
            ));
        }
    }
}
