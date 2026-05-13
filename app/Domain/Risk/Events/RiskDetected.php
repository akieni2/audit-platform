<?php

namespace App\Domain\Risk\Events;

final class RiskDetected extends AbstractRiskRegistryEvent
{
    public function eventName(): string
    {
        return 'risk.detected';
    }
}
