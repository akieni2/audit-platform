<?php

namespace App\Services\Workflow\Components;

use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStage;
use App\Services\Workflow\Components\Contracts\WorkflowStageComponent;
use App\Services\Workflow\WorkflowExecutionService;
use Illuminate\Http\Request;

class SystemStageComponent implements WorkflowStageComponent
{
    public function __construct(
        private WorkflowExecutionService $execution,
    ) {}

    public function key(): string
    {
        return 'system_stage';
    }

    public function aliases(): array
    {
        return [
            'system_stage',
            'stage-card',
            'mission-stage',
        ];
    }

    public function buildViewData(WorkflowInstance $instance, WorkflowStage $stage, ?User $actor = null): array
    {
        return [
            'view' => 'workflows.runtime.components.system-stage',
            'stage' => $stage,
            'instance' => $instance,
        ];
    }

    public function handleSubmission(Request $request, WorkflowInstance $instance, WorkflowStage $stage, ?User $actor = null): array
    {
        $this->execution->startStage($instance, $stage, $actor, ['component_key' => $stage->resolvedComponentKey()]);
        $updatedInstance = $this->execution->completeStage(
            $instance->fresh(['currentStage', 'stageExecutions.workflowStage']),
            $stage,
            $actor,
            [
                'manual_completion' => true,
                'component_key' => $stage->resolvedComponentKey(),
            ],
            'Étape système validée manuellement.'
        );

        return [
            'instance' => $updatedInstance,
            'message' => 'Étape système validée.',
        ];
    }
}
