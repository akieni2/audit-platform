<?php

namespace App\Services\Workflow;

use App\Domain\Workflow\Enums\WorkflowInstanceStatus;
use App\Domain\Workflow\Enums\WorkflowStageExecutionStatus;
use App\Domain\Workflow\Enums\WorkflowVisualState;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStage;
use App\Models\WorkflowStageExecution;
use Carbon\CarbonInterface;

class WorkflowVisualStateResolver
{
    public function resolve(WorkflowInstance $instance, WorkflowStage $stage, ?WorkflowStageExecution $execution = null): WorkflowVisualState
    {
        $execution ??= $instance->stageExecutions
            ->where('workflow_stage_id', $stage->id)
            ->sortByDesc('id')
            ->first();

        $instanceStatus = $instance->status instanceof WorkflowInstanceStatus
            ? $instance->status
            : WorkflowInstanceStatus::tryFrom((string) $instance->status);

        $executionStatus = $execution?->status instanceof WorkflowStageExecutionStatus
            ? $execution->status
            : WorkflowStageExecutionStatus::tryFrom((string) $execution?->status);

        if ($instanceStatus === WorkflowInstanceStatus::Cancelled) {
            return WorkflowVisualState::Archived;
        }

        if ($executionStatus === WorkflowStageExecutionStatus::Completed) {
            return WorkflowVisualState::Completed;
        }

        if ($executionStatus === WorkflowStageExecutionStatus::Skipped) {
            return WorkflowVisualState::Skipped;
        }

        if ($executionStatus === WorkflowStageExecutionStatus::Rejected) {
            return WorkflowVisualState::Failed;
        }

        if ($stage->requires_approval && (int) $instance->current_stage_id === (int) $stage->id) {
            return WorkflowVisualState::AwaitingApproval;
        }

        if ((int) $instance->current_stage_id === (int) $stage->id) {
            return $this->isStageOverdue($stage, $execution?->started_at)
                ? WorkflowVisualState::Blocked
                : WorkflowVisualState::Active;
        }

        if ($instanceStatus === WorkflowInstanceStatus::Completed && $executionStatus === WorkflowStageExecutionStatus::Pending) {
            return WorkflowVisualState::Archived;
        }

        return WorkflowVisualState::Pending;
    }

    /**
     * @return array<string, string>
     */
    public function present(WorkflowVisualState $state): array
    {
        return [
            'label' => $state->label(),
            'badge_classes' => $state->badgeClasses(),
            'card_classes' => $state->cardClasses(),
            'accent_color' => $state->accentColor(),
            'value' => $state->value,
        ];
    }

    private function isStageOverdue(WorkflowStage $stage, ?CarbonInterface $startedAt): bool
    {
        if (! $startedAt instanceof CarbonInterface) {
            return false;
        }

        $slaHours = (int) data_get($stage->resolvedConfiguration(), 'sla_hours', 0);

        return $slaHours > 0 && $startedAt->copy()->addHours($slaHours)->isPast();
    }
}
