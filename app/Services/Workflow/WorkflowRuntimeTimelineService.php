<?php

namespace App\Services\Workflow;

use App\DTOs\Workflow\WorkflowTimelineEntry;
use App\Models\BusinessEvent;
use App\Models\Mission;
use App\Models\MissionWorkflowEvent;
use App\Models\RuntimeMetric;
use App\Models\WorkflowExecutionLog;
use App\Models\WorkflowInstance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class WorkflowRuntimeTimelineService
{
    /**
     * @return Collection<int, WorkflowTimelineEntry>
     */
    public function build(WorkflowInstance $instance, int $limit = 80): Collection
    {
        $instance->loadMissing([
            'mission',
            'executionLogs.workflowStage',
            'executionLogs.actor',
            'stageExecutions.workflowStage',
        ]);

        $mission = $instance->mission;
        $entries = collect();

        $entries = $entries
            ->merge($this->workflowExecutionEntries($instance))
            ->merge($this->businessEventEntries($mission))
            ->merge($this->runtimeMetricEntries($mission))
            ->merge($this->missionWorkflowEntries($mission));

        return $entries
            ->sortByDesc(fn (WorkflowTimelineEntry $entry) => $entry->occurredAt->timestamp)
            ->take($limit)
            ->values();
    }

    /**
     * @return Collection<int, WorkflowTimelineEntry>
     */
    private function workflowExecutionEntries(WorkflowInstance $instance): Collection
    {
        return $instance->executionLogs->map(function (WorkflowExecutionLog $log) {
            return new WorkflowTimelineEntry(
                source: 'workflow_execution_logs',
                title: $log->message ?: $log->event_name,
                message: $log->event_name,
                status: (string) ($log->status ?? 'recorded'),
                tone: $this->toneForStatus((string) ($log->status ?? 'info')),
                occurredAt: $log->occurred_at ?? now(),
                actorName: $log->actor?->displayName(),
                stageName: $log->workflowStage?->name,
                payload: $log->payload ?? [],
            );
        });
    }

    /**
     * @return Collection<int, WorkflowTimelineEntry>
     */
    private function businessEventEntries(?Mission $mission): Collection
    {
        if (! $mission instanceof Mission || ! Schema::hasTable('business_events')) {
            return collect();
        }

        return BusinessEvent::query()
            ->where('mission_id', $mission->id)
            ->latest('occurred_at')
            ->limit(40)
            ->with('actor')
            ->get()
            ->map(function (BusinessEvent $event) {
                return new WorkflowTimelineEntry(
                    source: 'business_events',
                    title: $event->event_name,
                    message: data_get($event->payload, 'comment') ?: data_get($event->payload, 'action'),
                    status: (string) ($event->status ?? 'recorded'),
                    tone: $this->toneForStatus((string) ($event->status ?? 'info')),
                    occurredAt: $event->occurred_at ?? now(),
                    actorName: $event->actor?->displayName(),
                    payload: $event->payload ?? [],
                );
            });
    }

    /**
     * @return Collection<int, WorkflowTimelineEntry>
     */
    private function runtimeMetricEntries(?Mission $mission): Collection
    {
        if (! $mission instanceof Mission || ! Schema::hasTable('runtime_metrics')) {
            return collect();
        }

        return RuntimeMetric::query()
            ->where('scope_type', 'mission')
            ->where('scope_id', $mission->id)
            ->latest('recorded_at')
            ->limit(20)
            ->get()
            ->map(function (RuntimeMetric $metric) {
                return new WorkflowTimelineEntry(
                    source: 'runtime_metrics',
                    title: $metric->metric_key,
                    message: 'Valeur '.(string) $metric->value,
                    status: (string) ($metric->metric_type ?? 'gauge'),
                    tone: 'info',
                    occurredAt: $metric->recorded_at ?? now(),
                    payload: [
                        'metric_type' => $metric->metric_type,
                        'value' => $metric->value,
                        'dimensions' => $metric->dimensions ?? [],
                    ],
                );
            });
    }

    /**
     * @return Collection<int, WorkflowTimelineEntry>
     */
    private function missionWorkflowEntries(?Mission $mission): Collection
    {
        if (! $mission instanceof Mission) {
            return collect();
        }

        return MissionWorkflowEvent::query()
            ->where('mission_id', $mission->id)
            ->latest('created_at')
            ->with('user')
            ->limit(20)
            ->get()
            ->map(function (MissionWorkflowEvent $event) {
                return new WorkflowTimelineEntry(
                    source: 'mission_workflow_events',
                    title: 'Décision institutionnelle',
                    message: sprintf(
                        '%s: %s → %s',
                        (string) $event->action,
                        (string) $event->from_status,
                        (string) $event->to_status
                    ),
                    status: (string) $event->action,
                    tone: 'warning',
                    occurredAt: $event->created_at ?? now(),
                    actorName: $event->user?->displayName(),
                    payload: [
                        'comment' => $event->comment,
                    ],
                );
            });
    }

    private function toneForStatus(string $status): string
    {
        return match (strtolower($status)) {
            'completed', 'recorded', 'success', 'approved' => 'success',
            'rejected', 'failed', 'error' => 'critical',
            'awaiting_approval', 'warning', 'pending' => 'warning',
            default => 'info',
        };
    }
}
