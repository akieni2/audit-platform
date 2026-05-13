<?php

namespace App\Domain\Risk\Events;

use App\Models\IdentifiedRisk;
use App\Models\Risque;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RiskPromoted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public IdentifiedRisk $identifiedRisk,
        public Risque $risque,
    ) {}
}
