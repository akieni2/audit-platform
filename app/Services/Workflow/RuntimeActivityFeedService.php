<?php

namespace App\Services\Workflow;

use App\Models\WorkflowInstance;
use Illuminate\Support\Collection;

class RuntimeActivityFeedService
{
    public function __construct(
        private WorkflowRuntimeActivityFeedService $feed,
    ) {}

    /**
     * @return Collection<int, mixed>
     */
    public function latest(WorkflowInstance $instance, int $limit = 12): Collection
    {
        return $this->feed->latest($instance, $limit);
    }
}
