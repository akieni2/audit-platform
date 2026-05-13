<?php

namespace App\Domain\Risk\Events;

final class RiskClosed extends AbstractRiskRegistryEvent
{
    public function eventName(): string
    {
        return 'risk.closed';
    }
}
