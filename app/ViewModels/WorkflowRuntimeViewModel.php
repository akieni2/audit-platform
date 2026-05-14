<?php

namespace App\ViewModels;

use App\Models\User;
use App\Models\WorkflowInstance;
use App\Services\Workflow\WorkflowGraphBuilderService;
use App\Services\Workflow\WorkflowRuntimeActivityFeedService;
use App\Services\Workflow\WorkflowRuntimeProgressService;
use App\Services\Workflow\WorkflowRuntimeTimelineService;
use App\Services\Workflow\WorkflowStageUiRenderer;

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
    ) {}

    public static function build(
        WorkflowInstance $instance,
        ?User $actor,
        WorkflowRuntimeProgressService $progressService,
        WorkflowRuntimeTimelineService $timelineService,
        WorkflowRuntimeActivityFeedService $activityFeedService,
        WorkflowGraphBuilderService $graphBuilder,
        WorkflowStageUiRenderer $stageUiRenderer,
        \App\Services\Workflow\WorkflowEngineService $engine,
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
        );
    }
}
