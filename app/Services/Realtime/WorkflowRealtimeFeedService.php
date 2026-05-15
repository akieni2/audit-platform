<?php

namespace App\Services\Realtime;

use App\Models\Mission;
use App\Services\Workflow\RuntimeActivityFeedService;

class WorkflowRealtimeFeedService
{
    public function __construct(
        private RuntimeActivityFeedService $feed,
        private RuntimeBroadcastService $broadcast,
    ) {}

    /**
     * @return array{items: mixed, transport: string}
     */
    public function forMission(Mission $mission): array
    {
        $instance = $mission->workflowInstance;
        $items = $instance !== null
            ? $this->feed->latest($instance)
            : collect();

        return [
            'items' => $items,
            'transport' => $this->broadcast->shouldBroadcast() ? 'websocket' : 'polling',
        ];
    }
}
