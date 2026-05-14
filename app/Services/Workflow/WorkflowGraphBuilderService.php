<?php

namespace App\Services\Workflow;

use App\Models\WorkflowInstance;
use App\Models\WorkflowTransition;

class WorkflowGraphBuilderService
{
    public function __construct(
        private WorkflowRuntimeProgressService $progress,
    ) {}

    /**
     * @return array{nodes:list<array<string,mixed>>,edges:list<array<string,mixed>>}
     */
    public function build(WorkflowInstance $instance): array
    {
        $instance->loadMissing([
            'workflowTemplate.stages',
            'workflowTemplate.transitions.fromStage',
            'workflowTemplate.transitions.toStage',
            'stageExecutions.workflowStage',
        ]);

        $progress = $this->progress->summarize($instance);
        $items = collect($progress['items'] ?? [])->keyBy(fn (array $item) => $item['stage']->id);

        $nodes = $items->values()->map(function (array $item) {
            $stage = $item['stage'];
            $execution = $item['execution'];

            return [
                'id' => (int) $stage->id,
                'code' => (string) $stage->code,
                'label' => (string) $stage->name,
                'x' => (int) ($stage->position_x ?? 0),
                'y' => (int) ($stage->position_y ?? 0),
                'icon' => $stage->icon,
                'color' => $item['visual']['accent_color'],
                'state' => $item['visual_state']->value,
                'state_label' => $item['visual']['label'],
                'is_current' => (bool) $item['is_current'],
                'started_at' => $execution?->started_at,
                'completed_at' => $execution?->completed_at,
            ];
        })->all();

        $edges = $instance->workflowTemplate->transitions->map(function (WorkflowTransition $transition) use ($items) {
            $fromState = $items->get($transition->from_stage_id)['visual_state']?->value ?? 'pending';
            $toState = $items->get($transition->to_stage_id)['visual_state']?->value ?? 'pending';

            return [
                'id' => (int) $transition->id,
                'from' => (int) $transition->from_stage_id,
                'to' => (int) $transition->to_stage_id,
                'is_automatic' => (bool) $transition->is_automatic,
                'condition_type' => $transition->condition_type,
                'active_path' => $fromState === 'completed' && in_array($toState, ['active', 'awaiting_approval', 'completed'], true),
            ];
        })->values()->all();

        return compact('nodes', 'edges');
    }
}
