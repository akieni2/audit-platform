<?php

namespace App\Jobs;

use App\Services\Risk\MissionRiskProjectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshMissionRiskProjectionJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;

    public function __construct(
        public int $missionId,
        public ?string $correlationId = null,
    ) {
        $this->afterCommit();
        $this->queue = (string) config('core_runtime.projection_queue', 'projections');
        $this->tries = max(1, (int) config('core_runtime.projection_queue_tries', 3));
    }

    public function uniqueId(): string
    {
        return 'mission-risk-projection:'.$this->missionId;
    }

    public function handle(MissionRiskProjectionService $projections): void
    {
        $projections->refreshForMissionId(
            missionId: $this->missionId,
            force: false,
            correlationId: $this->correlationId,
        );
    }
}
