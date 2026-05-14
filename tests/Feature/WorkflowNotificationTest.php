<?php

namespace Tests\Feature;

use App\Models\WorkflowTransition;
use App\Notifications\WorkflowRuntimeNotification;
use App\Services\Workflow\WorkflowExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Concerns\BuildsWorkflowRuntimeContext;
use Tests\TestCase;

class WorkflowNotificationTest extends TestCase
{
    use BuildsWorkflowRuntimeContext;
    use RefreshDatabase;

    public function test_stage_completion_dispatches_runtime_notification_to_recipients(): void
    {
        Notification::fake();

        $department = $this->createDepartment('NTF');
        $auditeur = $this->createUser('charge_verification', $department, 30);
        $actor = $this->createUser('inspecteur_services', $department);
        $mission = $this->createMission($auditeur, $department);
        $workflow = $this->createWorkflowTemplate($department, 'notify');

        $stageA = $this->createStage($workflow, [
            'name' => 'Collecte',
            'code' => 'COLLECTE',
        ]);
        $stageB = $this->createStage($workflow, [
            'name' => 'Validation',
            'code' => 'VALIDATION',
            'sort_order' => 1,
        ]);

        WorkflowTransition::query()->create([
            'workflow_template_id' => $workflow->id,
            'from_stage_id' => $stageA->id,
            'to_stage_id' => $stageB->id,
            'is_automatic' => false,
        ]);

        $execution = app(WorkflowExecutionService::class);
        $instance = $execution->startWorkflow($mission, $workflow, $actor);
        $execution->completeStage($instance->fresh(['currentStage', 'stageExecutions.workflowStage']), $stageA, $actor, ['source' => 'test']);

        Notification::assertSentTo(
            $auditeur,
            WorkflowRuntimeNotification::class,
            function (WorkflowRuntimeNotification $notification) use ($auditeur): bool {
                $data = $notification->toDatabase($auditeur);

                return ($data['event_name'] ?? null) === 'workflow.stage.completed';
            }
        );
    }
}
