<?php

namespace App\Domain\Risk\Events;

final class RiskReviewed extends AbstractRiskRegistryEvent
{
    public function eventName(): string
    {
        return 'risk.reviewed';
    }
}
