<?php

namespace App\Domain\Risk\Events;

use App\Contracts\Runtime\StructuredBusinessEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

abstract class AbstractRiskRegistryEvent implements StructuredBusinessEvent
{
    use Dispatchable;
    use SerializesModels;

    public bool $afterCommit = true;

    public function __construct(
        public string $aggregateTypeValue,
        public int|string|null $aggregateIdValue,
        public ?int $missionId = null,
        public ?string $riskUuid = null,
        public ?int $actorId = null,
        public ?string $correlationId = null,
        public ?string $causationId = null,
    ) {
        $this->correlationId ??= (string) Str::uuid();
    }

    public function aggregateType(): string
    {
        return $this->aggregateTypeValue;
    }

    public function aggregateId(): int|string|null
    {
        return $this->aggregateIdValue;
    }

    public function context(): array
    {
        return [
            'correlation_id' => $this->correlationId,
            'causation_id' => $this->causationId,
            'actor_id' => $this->actorId,
            'mission_id' => $this->missionId,
            'risk_uuid' => $this->riskUuid,
        ];
    }
}
