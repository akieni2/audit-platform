<?php

namespace App\Services\Workflow;

use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStage;
use App\Notifications\WorkflowRuntimeNotification;
use Illuminate\Support\Collection;

class WorkflowRuntimeNotificationService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function notifyStageCompleted(
        WorkflowInstance $instance,
        WorkflowStage $stage,
        ?User $actor = null,
        array $payload = [],
    ): void {
        $this->notify(
            instance: $instance,
            stage: $stage,
            eventName: 'workflow.stage.completed',
            title: 'Workflow — étape complétée',
            body: sprintf('L’étape "%s" a été complétée.', $stage->name),
            actor: $actor,
            payload: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function notifyApprovalRequired(
        WorkflowInstance $instance,
        WorkflowStage $stage,
        ?User $actor = null,
        array $payload = [],
    ): void {
        $this->notify(
            instance: $instance,
            stage: $stage,
            eventName: 'workflow.approval.required',
            title: 'Workflow — approbation requise',
            body: sprintf('Une approbation est requise pour l’étape "%s".', $stage->name),
            actor: $actor,
            payload: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function notifyWorkflowBlocked(
        WorkflowInstance $instance,
        WorkflowStage $stage,
        ?User $actor = null,
        array $payload = [],
    ): void {
        $this->notify(
            instance: $instance,
            stage: $stage,
            eventName: 'workflow.blocked',
            title: 'Workflow — blocage détecté',
            body: sprintf('Le workflow est bloqué sur l’étape "%s".', $stage->name),
            actor: $actor,
            payload: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function notifyRiskPromoted(
        WorkflowInstance $instance,
        WorkflowStage $stage,
        ?User $actor = null,
        array $payload = [],
    ): void {
        $this->notify(
            instance: $instance,
            stage: $stage,
            eventName: 'workflow.risk.promoted',
            title: 'Workflow — risque promu',
            body: sprintf('Un risque a été promu depuis l’étape "%s".', $stage->name),
            actor: $actor,
            payload: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function notifyOverdueStage(
        WorkflowInstance $instance,
        WorkflowStage $stage,
        ?User $actor = null,
        array $payload = [],
    ): void {
        $this->notify(
            instance: $instance,
            stage: $stage,
            eventName: 'workflow.stage.overdue',
            title: 'Workflow — étape en retard',
            body: sprintf('L’étape "%s" a dépassé son SLA.', $stage->name),
            actor: $actor,
            payload: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function notifyWorkflowCompleted(
        WorkflowInstance $instance,
        ?User $actor = null,
        array $payload = [],
    ): void {
        $this->notify(
            instance: $instance,
            stage: $instance->currentStage,
            eventName: 'workflow.completed',
            title: 'Workflow — exécution terminée',
            body: 'Le workflow de mission est terminé.',
            actor: $actor,
            payload: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function notify(
        WorkflowInstance $instance,
        ?WorkflowStage $stage,
        string $eventName,
        string $title,
        string $body,
        ?User $actor = null,
        array $payload = [],
    ): void {
        $recipients = $this->resolveRecipients($instance, $stage, $actor);

        foreach ($recipients as $recipient) {
            $recipient->notify(new WorkflowRuntimeNotification(
                instance: $instance,
                stage: $stage,
                eventName: $eventName,
                title: $title,
                body: $body,
                actor: $actor,
                payload: $payload,
            ));
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function resolveRecipients(WorkflowInstance $instance, ?WorkflowStage $stage, ?User $actor = null): Collection
    {
        $instance->loadMissing([
            'mission.auditeur',
            'currentStage.approvalRole',
            'stageExecutions.assignee',
        ]);

        $users = collect();

        if ($instance->mission?->auditeur instanceof User) {
            $users->push($instance->mission->auditeur);
        }

        $activeExecution = $instance->stageExecutions
            ->where('workflow_stage_id', $stage?->id)
            ->sortByDesc('id')
            ->first();

        if ($activeExecution?->assignee instanceof User) {
            $users->push($activeExecution->assignee);
        }

        if ($stage?->approvalRole?->id) {
            $approvalUsers = User::query()
                ->where('role_id', $stage->approvalRole->id)
                ->where('active', true)
                ->get();

            $users = $users->merge($approvalUsers);
        }

        return $users
            ->filter(fn ($user) => $user instanceof User)
            ->when($actor instanceof User, fn (Collection $collection) => $collection->reject(fn (User $user) => $user->is($actor)))
            ->unique('id')
            ->values();
    }
}
