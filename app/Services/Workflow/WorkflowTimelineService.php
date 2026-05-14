<?php

namespace App\Services\Workflow;

use App\Models\WorkflowInstance;
use Illuminate\Support\Collection;

class WorkflowTimelineService
{
    public function __construct(
        private WorkflowRuntimeTimelineService $timeline,
    ) {}

    /**
     * @return Collection<int, mixed>
     */
    public function build(WorkflowInstance $instance): Collection
    {
        return $this->timeline->build($instance);
    }
}
