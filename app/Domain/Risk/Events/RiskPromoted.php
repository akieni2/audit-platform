<?php

namespace App\Domain\Risk\Events;

use App\Contracts\Runtime\StructuredBusinessEvent;
use App\Models\IdentifiedRisk;
use App\Models\Risque;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class RiskPromoted implements StructuredBusinessEvent
{
    use Dispatchable;
    use SerializesModels;

    public bool $afterCommit = true;

    public function __construct(
        public IdentifiedRisk $identifiedRisk,
        public Risque $risque,
        public ?string $correlationId = null,
    ) {
        $this->correlationId ??= (string) Str::uuid();
    }

    public function eventName(): string
    {
        return 'risk.promoted';
    }

    public function aggregateType(): string
    {
        return 'identified_risk';
    }

    public function aggregateId(): int|string|null
    {
        return $this->identifiedRisk->id;
    }

    public function context(): array
    {
        return [
            'correlation_id' => $this->correlationId,
            'mission_id' => $this->identifiedRisk->mission_id,
            'risque_id' => $this->risque->id,
        ];
    }
}
