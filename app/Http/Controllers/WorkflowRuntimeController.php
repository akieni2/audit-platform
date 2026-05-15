<?php

namespace App\Http\Controllers;

use App\Models\BusinessEvent;
use App\Models\Mission;
use App\Models\ProjectionIntegrityCheck;
use App\Models\RaciAuditLog;
use App\Models\RuntimeMetric;
use App\Models\SwotAuditLog;
use App\Models\WorkflowStage;
use App\Models\WorkflowTemplate;
use App\Services\Observability\ErrorAggregationService;
use App\Services\Observability\ProjectionHealthService;
use App\Services\Observability\QueueMonitoringService;
use App\Services\Observability\RuntimeHealthVisualizationService;
use App\Services\Workflow\WorkflowCompatibilityService;
use App\Services\Workflow\WorkflowEngineService;
use App\Services\Workflow\WorkflowExecutionService;
use App\Services\Workflow\RuntimeActivityFeedService;
use App\Services\Workflow\WorkflowRuntimeDashboardService;
use App\Services\Workflow\WorkflowProgressEngine;
use App\Services\Workflow\WorkflowTimelineService;
use App\Services\Workflow\WorkflowVisualStateService;
use App\Services\Runtime\RuntimeRecommendationService;
use App\ViewModels\WorkflowRuntimeViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkflowRuntimeController extends Controller
{
    public function __construct(
        private WorkflowCompatibilityService $compatibility,
        private WorkflowExecutionService $execution,
        private WorkflowEngineService $engine,
        private WorkflowProgressEngine $progress,
        private WorkflowTimelineService $timeline,
        private RuntimeActivityFeedService $activityFeed,
        private \App\Services\Workflow\WorkflowGraphBuilderService $graphBuilder,
        private \App\Services\Workflow\WorkflowStageUiRenderer $stageUiRenderer,
        private WorkflowRuntimeDashboardService $dashboard,
        private WorkflowVisualStateService $visualStates,
        private RuntimeRecommendationService $recommendations,
        private RuntimeHealthVisualizationService $runtimeHealth,
        private QueueMonitoringService $queueMonitoring,
        private ProjectionHealthService $projectionHealth,
        private ErrorAggregationService $errors,
    ) {}

    public function show(Request $request, Mission $mission): View
    {
        $this->authorize('view', $mission);
        $actor = $request->user();
        abort_unless($actor, 403);

        $instance = $this->compatibility->ensureMissionWorkflow($mission, $actor);
        $viewModel = WorkflowRuntimeViewModel::build(
            instance: $instance,
            actor: $actor,
            progressService: $this->progress,
            timelineService: $this->timeline,
            activityFeedService: $this->activityFeed,
            graphBuilder: $this->graphBuilder,
            stageUiRenderer: $this->stageUiRenderer,
            engine: $this->engine,
            visualStates: $this->visualStates,
        );

        return view('workflows.runtime.show', [
            'mission' => $mission,
            'runtime' => $viewModel,
            'runtimeRecommendations' => $this->recommendations->forStage($instance, $instance->currentStage),
        ]);
    }

    public function transition(Request $request, Mission $mission): RedirectResponse
    {
        $this->authorize('view', $mission);
        $actor = $request->user();
        abort_unless($actor, 403);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['skip', 'retry', 'rollback', 'reopen', 'approve', 'reject'])],
            'stage_id' => ['nullable', 'integer'],
            'target_stage_id' => ['nullable', 'integer'],
            'comment' => ['nullable', 'string'],
        ]);

        $instance = $this->compatibility->ensureMissionWorkflow($mission, $actor);
        $instance->loadMissing('currentStage', 'workflowTemplate.stages');

        $stage = $instance->workflowTemplate?->stages->firstWhere('id', (int) ($validated['stage_id'] ?? $instance->current_stage_id));
        $targetStage = $instance->workflowTemplate?->stages->firstWhere('id', (int) ($validated['target_stage_id'] ?? 0));
        $comment = $validated['comment'] ?? null;

        match ($validated['action']) {
            'skip' => $stage && $this->execution->skipStage($instance, $stage, $actor, $comment),
            'retry' => $stage && $this->execution->retryStage($instance, $stage, $actor, $comment),
            'rollback' => $targetStage && $this->execution->rollbackToStage($instance, $targetStage, $actor, $comment),
            'reopen' => $this->execution->reopenWorkflow($instance, $actor, $targetStage, $comment),
            'approve' => $stage && $this->execution->approveStage($instance, $stage, $actor, $comment),
            'reject' => $stage && $this->execution->rejectStage($instance, $stage, $actor, $comment),
            default => null,
        };

        return redirect()
            ->route('workflow-runtime.show', $mission)
            ->with('status', 'Action runtime enregistrée.');
    }

    public function dashboard(Request $request): View
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        return view('workflows.dashboard.index', [
            'runtimeDashboard' => $this->dashboard->buildForUser($actor),
        ]);
    }

    public function observability(Request $request): View
    {
        $actor = $request->user();
        abort_unless($actor, 403);

        $missionIds = Mission::query()->visibleToUser($actor)->pluck('id');
        $businessEvents = Schema::hasTable('business_events')
            ? BusinessEvent::query()->whereIn('mission_id', $missionIds)->latest('occurred_at')->limit(50)->with('actor')->get()
            : collect();
        $runtimeMetrics = Schema::hasTable('runtime_metrics')
            ? RuntimeMetric::query()->where('scope_type', 'mission')->whereIn('scope_id', $missionIds)->latest('recorded_at')->limit(50)->get()
            : collect();
        $integrityChecks = Schema::hasTable('projection_integrity_checks')
            ? ProjectionIntegrityCheck::query()->latest('checked_at')->limit(30)->get()
            : collect();
        $workflowTemplates = WorkflowTemplate::query()->withCount(['instances', 'stages'])->latest('updated_at')->limit(20)->get();
        $swotAuditLogs = Schema::hasTable('swot_audit_logs')
            ? SwotAuditLog::query()->latest('occurred_at')->limit(25)->with('actor')->get()
            : collect();
        $raciAuditLogs = Schema::hasTable('raci_audit_logs')
            ? RaciAuditLog::query()->latest('occurred_at')->limit(25)->with('actor')->get()
            : collect();

        return view('observability.center', [
            'businessEvents' => $businessEvents,
            'runtimeMetrics' => $runtimeMetrics,
            'integrityChecks' => $integrityChecks,
            'workflowTemplates' => $workflowTemplates,
            'swotAuditLogs' => $swotAuditLogs,
            'raciAuditLogs' => $raciAuditLogs,
            'runtimeHealth' => $this->runtimeHealth->build($businessEvents, $runtimeMetrics, $workflowTemplates),
            'queueHealth' => $this->queueMonitoring->snapshot($workflowTemplates),
            'projectionHealth' => $this->projectionHealth->snapshot($integrityChecks),
            'errorSummary' => $this->errors->summarize($businessEvents, $runtimeMetrics, $integrityChecks),
        ]);
    }
}
