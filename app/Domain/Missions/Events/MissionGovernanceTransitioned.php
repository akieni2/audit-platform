<?php

namespace App\Domain\Missions\Events;

use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MissionGovernanceTransitioned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Mission $mission,
        public User $actor,
        public string $action,
        public string $fromStatus,
        public string $toStatus,
        public ?string $comment = null,
    ) {}
}
