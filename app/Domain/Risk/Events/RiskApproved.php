<?php

namespace App\Domain\Risk\Events;

final class RiskApproved extends AbstractRiskRegistryEvent
{
    public function eventName(): string
    {
        return 'risk.approved';
    }
}
