<?php

namespace App\Services\Workflow;

use App\Models\IdentifiedRisk;
use App\Models\WorkflowInstance;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class WorkflowRuntimeDashboardService
{
    public function __construct(
        private WorkflowRuntimeProgressService $progress,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildForUser(User $user): array
    {
        $missionIds = \App\Models\Mission::query()
            ->visibleToUser($user)
            ->pluck('id');

        $instances = WorkflowInstance::query()
            ->with(['mission.department', 'currentStage', 'workflowTemplate', 'stageExecutions.workflowStage'])
            ->whereIn('mission_id', $missionIds)
            ->latest('started_at')
            ->get();

        $cards = $instances->map(function (WorkflowInstance $instance) {
            $summary = $this->progress->summarize($instance);

            return [
                'instance' => $instance,
                'summary' => $summary,
            ];
        });

        $running = $cards->filter(fn (array $card) => (string) ($card['instance']->status?->value ?? $card['instance']->status) === 'running');
        $blocked = $cards->filter(fn (array $card) => ($card['summary']['blocked_count'] ?? 0) > 0);
        $awaitingApproval = $cards->filter(fn (array $card) => ($card['summary']['awaiting_approval_count'] ?? 0) > 0);

        return [
            'instances' => $cards,
            'kpis' => [
                'active_workflows' => $running->count(),
                'blocked_workflows' => $blocked->count(),
                'awaiting_approval' => $awaitingApproval->count(),
                'completion_rate' => (int) round($cards->pluck('summary.completion_percent')->avg() ?: 0),
                'average_execution_minutes' => (int) round($cards->pluck('summary.average_duration_minutes')->avg() ?: 0),
                'detected_risks' => Schema::hasTable('identified_risks')
                    ? IdentifiedRisk::query()->whereIn('mission_id', $missionIds)->count()
                    : 0,
            ],
            'heatmap' => $this->heatmapBuckets($cards),
        ];
    }

    /**
     * @param  Collection<int, array{instance:WorkflowInstance,summary:array<string,mixed>}>  $cards
     * @return array<string, int>
     */
    private function heatmapBuckets(Collection $cards): array
    {
        return [
            'healthy' => $cards->filter(fn (array $card) => ($card['summary']['blocked_count'] ?? 0) === 0 && ($card['summary']['failed_count'] ?? 0) === 0)->count(),
            'attention' => $cards->filter(fn (array $card) => ($card['summary']['awaiting_approval_count'] ?? 0) > 0)->count(),
            'critical' => $cards->filter(fn (array $card) => ($card['summary']['blocked_count'] ?? 0) > 0 || ($card['summary']['failed_count'] ?? 0) > 0)->count(),
        ];
    }
}
