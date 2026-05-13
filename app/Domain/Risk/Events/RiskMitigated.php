<?php

namespace App\Domain\Risk\Events;

final class RiskMitigated extends AbstractRiskRegistryEvent
{
    public function eventName(): string
    {
        return 'risk.mitigated';
    }
}
