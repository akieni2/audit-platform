<?php

namespace App\Services\Workflow;

use App\Models\WorkflowInstance;

class WorkflowProgressEngine
{
    public function __construct(
        private WorkflowRuntimeProgressService $progress,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summarize(WorkflowInstance $instance): array
    {
        $summary = $this->progress->summarize($instance);
        $summary['global_state'] = $instance->status?->value ?? $instance->status ?? 'draft';
        $summary['navigation'] = [
            'current_stage_id' => $instance->current_stage_id,
            'has_previous' => collect($summary['items'] ?? [])->contains(fn ($item) => ($item['status'] ?? null) === 'completed'),
            'has_next' => collect($summary['items'] ?? [])->contains(fn ($item) => ($item['status'] ?? null) === 'pending'),
        ];

        return $summary;
    }
}
