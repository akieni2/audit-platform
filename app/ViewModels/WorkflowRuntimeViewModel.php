<?php

namespace App\ViewModels;

use App\Models\User;
use App\Models\WorkflowInstance;
use App\Services\Workflow\WorkflowGraphBuilderService;
use App\Services\Workflow\RuntimeActivityFeedService;
use App\Services\Workflow\WorkflowProgressEngine;
use App\Services\Workflow\WorkflowStageUiRenderer;
use App\Services\Workflow\WorkflowTimelineService;
use App\Services\Workflow\WorkflowVisualStateService;

class WorkflowRuntimeViewModel
{
    public function __construct(
        public readonly WorkflowInstance $instance,
        public readonly array $progress,
        public readonly array $graph,
        public readonly \Illuminate\Support\Collection $timeline,
        public readonly \Illuminate\Support\Collection $activityFeed,
        public readonly ?array $currentStageUi,
        public readonly \Illuminate\Support\Collection $availableTransitions,
        public readonly array $workflowState,
    ) {}

    public static function build(
        WorkflowInstance $instance,
        ?User $actor,
        WorkflowProgressEngine $progressService,
        WorkflowTimelineService $timelineService,
        RuntimeActivityFeedService $activityFeedService,
        WorkflowGraphBuilderService $graphBuilder,
        WorkflowStageUiRenderer $stageUiRenderer,
        \App\Services\Workflow\WorkflowEngineService $engine,
        WorkflowVisualStateService $visualStates,
    ): self {
        $instance->loadMissing([
            'currentStage',
            'currentStage.formTemplate',
            'currentStage.questionnaireTemplate',
            'workflowTemplate.stages',
            'workflowTemplate.transitions.fromStage',
            'workflowTemplate.transitions.toStage',
            'stageExecutions.workflowStage',
            'stageExecutions.assignee',
            'executionLogs.actor',
            'executionLogs.workflowStage',
            'mission',
        ]);

        $currentStageUi = $instance->currentStage
            ? $stageUiRenderer->render($instance, $instance->currentStage, $actor)
            : null;

        return new self(
            instance: $instance,
            progress: $progressService->summarize($instance),
            graph: $graphBuilder->build($instance),
            timeline: $timelineService->build($instance),
            activityFeed: $activityFeedService->latest($instance),
            currentStageUi: $currentStageUi,
            availableTransitions: $engine->availableTransitions($instance, $actor),
            workflowState: $instance->currentStage
                ? $visualStates->resolve($instance, $instance->currentStage)
                : [
                    'value' => $instance->status?->value ?? $instance->status ?? 'draft',
                    'label' => 'Sans étape active',
                    'badge_classes' => 'bg-[#17223B] text-[#BFD2E6]',
                    'card_classes' => 'border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)]',
                    'accent_color' => '#73D8FF',
                ],
        );
    }
}
