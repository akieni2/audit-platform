<?php

namespace Tests\Feature;

use App\Models\BusinessEvent;
use App\Models\MissionWorkflowEvent;
use App\Models\RuntimeMetric;
use App\Models\WorkflowTransition;
use App\Services\Workflow\WorkflowExecutionService;
use App\Services\Workflow\WorkflowRuntimeTimelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsWorkflowRuntimeContext;
use Tests\TestCase;

class WorkflowTimelineTest extends TestCase
{
    use BuildsWorkflowRuntimeContext;
    use RefreshDatabase;

    public function test_timeline_aggregates_workflow_logs_business_events_metrics_and_governance_history(): void
    {
        $department = $this->createDepartment('TLN');
        $user = $this->createUser('inspecteur_services', $department);
        $mission = $this->createMission($user, $department);
        $workflow = $this->createWorkflowTemplate($department, 'timeline');

        $stageA = $this->createStage($workflow, [
            'name' => 'Collecte',
            'code' => 'COLLECTE',
        ]);
        $stageB = $this->createStage($workflow, [
            'name' => 'Reporting',
            'code' => 'REPORTING',
            'sort_order' => 1,
        ]);

        WorkflowTransition::query()->create([
            'workflow_template_id' => $workflow->id,
            'from_stage_id' => $stageA->id,
            'to_stage_id' => $stageB->id,
            'is_automatic' => false,
        ]);

        $execution = app(WorkflowExecutionService::class);
        $instance = $execution->startWorkflow($mission, $workflow, $user);
        $execution->completeStage($instance->fresh(['currentStage', 'stageExecutions.workflowStage']), $stageA, $user, ['summary' => 'ok']);

        BusinessEvent::query()->create([
            'event_name' => 'core_runtime.test.timeline',
            'aggregate_type' => 'mission',
            'aggregate_id' => (string) $mission->id,
            'mission_id' => $mission->id,
            'actor_user_id' => $user->id,
            'status' => 'recorded',
            'payload' => ['foo' => 'bar'],
            'context' => [],
            'occurred_at' => now()->subMinute(),
        ]);

        RuntimeMetric::query()->create([
            'metric_key' => 'workflow.timeline.test',
            'metric_type' => 'counter',
            'scope_type' => 'mission',
            'scope_id' => $mission->id,
            'dimensions_hash' => sha1('timeline'),
            'dimensions' => ['suite' => 'timeline'],
            'value' => 1,
            'recorded_at' => now()->subSeconds(30),
        ]);

        MissionWorkflowEvent::query()->create([
            'mission_id' => $mission->id,
            'user_id' => $user->id,
            'action' => 'valider_is',
            'from_status' => 'clôturée',
            'to_status' => 'validée_IS',
            'comment' => 'Validation test',
        ]);

        $timeline = app(WorkflowRuntimeTimelineService::class)->build($instance->fresh());

        $this->assertTrue($timeline->contains(fn ($entry) => $entry->source === 'workflow_execution_logs'));
        $this->assertTrue($timeline->contains(fn ($entry) => $entry->source === 'business_events'));
        $this->assertTrue($timeline->contains(fn ($entry) => $entry->source === 'runtime_metrics'));
        $this->assertTrue($timeline->contains(fn ($entry) => $entry->source === 'mission_workflow_events'));
    }
}
