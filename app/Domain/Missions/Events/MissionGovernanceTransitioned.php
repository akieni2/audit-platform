<?php

namespace App\Domain\Missions\Events;

use App\Contracts\Runtime\StructuredBusinessEvent;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class MissionGovernanceTransitioned implements StructuredBusinessEvent
{
    use Dispatchable;
    use SerializesModels;

    public bool $afterCommit = true;

    public function __construct(
        public Mission $mission,
        public User $actor,
        public string $action,
        public string $fromStatus,
        public string $toStatus,
        public ?string $comment = null,
        public ?string $correlationId = null,
    ) {
        $this->correlationId ??= (string) Str::uuid();
    }

    public function eventName(): string
    {
        return 'mission.governance_transitioned';
    }

    public function aggregateType(): string
    {
        return 'mission';
    }

    public function aggregateId(): int|string|null
    {
        return $this->mission->id;
    }

    public function context(): array
    {
        return [
            'correlation_id' => $this->correlationId,
            'mission_id' => $this->mission->id,
            'action' => $this->action,
            'from_status' => $this->fromStatus,
            'to_status' => $this->toStatus,
        ];
    }
}
