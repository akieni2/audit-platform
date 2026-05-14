<?php

namespace App\Services\Workflow;

use App\Domain\Workflow\Enums\WorkflowVisualState;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStage;
use App\Models\WorkflowStageExecution;

class WorkflowRuntimeProgressService
{
    public function __construct(
        private WorkflowVisualStateResolver $visualStates,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summarize(WorkflowInstance $instance): array
    {
        $instance->loadMissing([
            'workflowTemplate.stages',
            'stageExecutions.workflowStage',
            'currentStage',
        ]);

        $stages = $instance->workflowTemplate?->stages?->sortBy('sort_order')->values() ?? collect();
        $executions = $instance->stageExecutions->keyBy('workflow_stage_id');

        $items = $stages->map(function (WorkflowStage $stage) use ($instance, $executions) {
            /** @var WorkflowStageExecution|null $execution */
            $execution = $executions->get($stage->id);
            $visualState = $this->visualStates->resolve($instance, $stage, $execution);

            return [
                'stage' => $stage,
                'execution' => $execution,
                'visual_state' => $visualState,
                'visual' => $this->visualStates->present($visualState),
                'is_current' => (int) $instance->current_stage_id === (int) $stage->id,
                'duration_minutes' => $execution?->started_at && $execution?->completed_at
                    ? $execution->started_at->diffInMinutes($execution->completed_at)
                    : null,
            ];
        })->values();

        $total = max(1, $items->count());
        $completed = $items->filter(fn (array $item) => $item['visual_state'] === WorkflowVisualState::Completed)->count();
        $blocked = $items->filter(fn (array $item) => $item['visual_state'] === WorkflowVisualState::Blocked)->count();
        $awaitingApproval = $items->filter(fn (array $item) => $item['visual_state'] === WorkflowVisualState::AwaitingApproval)->count();
        $failed = $items->filter(fn (array $item) => $item['visual_state'] === WorkflowVisualState::Failed)->count();

        return [
            'items' => $items,
            'total_count' => $items->count(),
            'completed_count' => $completed,
            'active_count' => $items->filter(fn (array $item) => $item['visual_state'] === WorkflowVisualState::Active)->count(),
            'blocked_count' => $blocked,
            'failed_count' => $failed,
            'awaiting_approval_count' => $awaitingApproval,
            'completion_percent' => (int) round(($completed / $total) * 100),
            'current_stage' => $instance->currentStage,
            'average_duration_minutes' => (int) round($items->pluck('duration_minutes')->filter()->avg() ?: 0),
        ];
    }
}
