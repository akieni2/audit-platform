<?php

namespace App\Services\Workflow;

use App\Models\WorkflowInstance;
use Illuminate\Support\Collection;

class WorkflowRuntimeActivityFeedService
{
    public function __construct(
        private WorkflowRuntimeTimelineService $timeline,
    ) {}

    /**
     * @return Collection<int, \App\DTOs\Workflow\WorkflowTimelineEntry>
     */
    public function latest(WorkflowInstance $instance, int $limit = 12): Collection
    {
        return $this->timeline->build($instance, $limit);
    }
}
