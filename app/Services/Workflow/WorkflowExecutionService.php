<?php

namespace App\Services\Workflow;

use App\Domain\Workflow\Enums\WorkflowExecutionMode;
use App\Domain\Workflow\Enums\WorkflowStageType;
use App\Models\ActionCorrective;
use App\Models\Entretien;
use App\Models\IdentifiedRisk;
use App\Models\Mission;
use App\Models\MissionDocument;
use App\Models\MissionRiskProjection;
use App\Models\Risque;
use App\Models\User;
use App\Models\WorkflowExecutionLog;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStage;
use App\Models\WorkflowStageExecution;
use App\Models\WorkflowTemplate;
use App\Models\WorkflowTransition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class WorkflowExecutionService
{
    public function __construct(
        private WorkflowEngineService $engine,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function startWorkflow(
        Mission $mission,
        WorkflowTemplate $template,
        ?User $actor = null,
        array $metadata = [],
    ): WorkflowInstance {
        $instance = $this->engine->start($mission, $template, $actor, $metadata);

        $this->logExecution(
            instance: $instance,
            execution: $instance->stageExecutions->firstWhere('workflow_stage_id', $instance->current_stage_id),
            stage: $instance->currentStage,
            eventName: 'workflow.execution.started',
            status: $instance->status?->value ?? (string) $instance->status,
            message: 'Workflow démarré.',
            actor: $actor,
            payload: ['workflow_template_id' => $template->id]
        );

        return $instance;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function startStage(
        WorkflowInstance $instance,
        WorkflowStage $stage,
        ?User $actor = null,
        array $payload = [],
        ?string $message = null,
    ): WorkflowStageExecution {
        $execution = WorkflowStageExecution::query()->firstOrCreate(
            [
                'workflow_instance_id' => $instance->id,
                'workflow_stage_id' => $stage->id,
            ],
            [
                'status' => 'active',
                'started_at' => now(),
                'assigned_to' => $actor?->id,
            ]
        );

        $execution->forceFill([
            'status' => 'active',
            'started_at' => $execution->started_at ?? now(),
            'assigned_to' => $actor?->id ?? $execution->assigned_to,
            'payload' => array_replace_recursive($execution->payload ?? [], $payload),
        ])->save();

        $this->logExecution(
            instance: $instance,
            execution: $execution,
            stage: $stage,
            eventName: 'workflow.stage.started',
            status: 'active',
            message: $message ?? 'Étape démarrée.',
            actor: $actor,
            payload: $payload,
        );

        return $execution;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function completeStage(
        WorkflowInstance $instance,
        WorkflowStage $stage,
        ?User $actor = null,
        array $payload = [],
        ?string $notes = null,
    ): WorkflowInstance {
        if ((int) $instance->current_stage_id !== (int) $stage->id) {
            throw new InvalidArgumentException('Impossible de compléter une étape qui n’est pas active.');
        }

        $transition = $this->resolveNextTransition($instance, $actor);
        $fresh = $this->engine->advance($instance, $actor, $transition, $payload, $notes);

        $this->logExecution(
            instance: $fresh,
            execution: $instance->stageExecutions()->where('workflow_stage_id', $stage->id)->latest('id')->first(),
            stage: $stage,
            eventName: 'workflow.stage.completed',
            status: 'completed',
            message: $notes ?? 'Étape complétée.',
            actor: $actor,
            payload: $payload,
        );

        return $fresh;
    }

    public function validateTransition(WorkflowInstance $instance, WorkflowTransition $transition, ?User $actor = null): bool
    {
        return $this->engine->verifyTransition($instance, $transition, $actor);
    }

    public function resolveNextTransition(WorkflowInstance $instance, ?User $actor = null, ?WorkflowStage $targetStage = null): ?WorkflowTransition
    {
        $transitions = $this->engine->availableTransitions($instance, $actor)
            ->sortBy(fn (WorkflowTransition $transition) => $transition->toStage?->sort_order ?? PHP_INT_MAX)
            ->values();

        if (! $targetStage instanceof WorkflowStage) {
            return $transitions->first();
        }

        return $transitions->first(
            fn (WorkflowTransition $transition) => (int) $transition->to_stage_id === (int) $targetStage->id
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function executeRules(WorkflowInstance $instance, WorkflowStage $stage): array
    {
        $configuration = $stage->resolvedConfiguration();

        $this->logExecution(
            instance: $instance,
            execution: $instance->stageExecutions()->where('workflow_stage_id', $stage->id)->latest('id')->first(),
            stage: $stage,
            eventName: 'workflow.stage.rules_evaluated',
            status: 'evaluated',
            message: 'Règles d’exécution évaluées.',
            actor: null,
            payload: $configuration,
        );

        return $configuration;
    }

    public function syncMissionCompatibility(Mission $mission, ?User $actor = null): ?WorkflowInstance
    {
        $mission->loadMissing('workflowInstance.currentStage', 'workflowInstance.stageExecutions.workflowStage');

        if (! $mission->workflowInstance instanceof WorkflowInstance) {
            return null;
        }

        return $this->syncInstance($mission->workflowInstance, $actor);
    }

    public function syncInstance(WorkflowInstance $instance, ?User $actor = null): WorkflowInstance
    {
        $instance->loadMissing([
            'workflowTemplate.stages.questionnaireTemplate',
            'currentStage.questionnaireTemplate',
            'stageExecutions.workflowStage',
            'mission',
        ]);

        $guard = 0;
        $current = $instance;

        while (! $this->engine->isCompleted($current) && $current->currentStage instanceof WorkflowStage) {
            $stage = $current->currentStage;

            if (! $this->stageIsSatisfied($current, $stage)) {
                break;
            }

            $this->executeRules($current, $stage);
            $this->startStage($current, $stage, $actor, ['auto_sync' => true], 'Étape activée par synchronisation.');
            $current = $this->completeStage(
                $current,
                $stage,
                $actor,
                [
                    'auto_sync' => true,
                    'stage_code' => $stage->code,
                    'synchronized_at' => now()->toIso8601String(),
                ],
                'Étape complétée automatiquement par synchronisation.'
            );

            $guard++;
            if ($guard > 25) {
                break;
            }
        }

        return $current->fresh([
            'workflowTemplate.stages.questionnaireTemplate',
            'currentStage.questionnaireTemplate',
            'stageExecutions.workflowStage',
            'executionLogs',
            'mission',
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function logExecution(
        WorkflowInstance $instance,
        ?WorkflowStageExecution $execution,
        ?WorkflowStage $stage,
        string $eventName,
        ?string $status,
        ?string $message,
        ?User $actor = null,
        ?array $payload = null,
    ): ?WorkflowExecutionLog {
        if (! Schema::hasTable('workflow_execution_logs')) {
            return null;
        }

        return WorkflowExecutionLog::query()->create([
            'workflow_instance_id' => $instance->id,
            'workflow_stage_execution_id' => $execution?->id,
            'workflow_stage_id' => $stage?->id,
            'event_name' => $eventName,
            'status' => $status,
            'message' => $message,
            'payload' => $payload,
            'actor_id' => $actor?->id,
            'occurred_at' => now(),
        ]);
    }

    public function stageIsSatisfied(WorkflowInstance $instance, WorkflowStage $stage): bool
    {
        $instance->loadMissing('mission');
        $mission = $instance->mission;

        if (! $mission instanceof Mission) {
            return false;
        }

        $stageType = $stage->resolvedStageType();
        $executionMode = $stage->resolvedExecutionMode();

        return match ($stageType) {
            WorkflowStageType::Mission => filled($mission->organisation) && filled($mission->date_debut),
            WorkflowStageType::ServiceSelection => Schema::hasTable('services') && $mission->services()->exists(),
            WorkflowStageType::Questionnaire => $this->questionnaireStageSatisfied($mission, $stage),
            WorkflowStageType::Form => $this->formStageSatisfied($instance, $stage),
            WorkflowStageType::RiskCapture => $this->riskCaptureSatisfied($mission),
            WorkflowStageType::Heatmap => Schema::hasTable('mission_risk_projections')
                && MissionRiskProjection::query()->where('mission_id', $mission->id)->exists(),
            WorkflowStageType::DocumentReview => Schema::hasTable('mission_documents')
                && MissionDocument::query()->where('mission_id', $mission->id)->exists(),
            WorkflowStageType::Approval => $this->approvalStageSatisfied($mission, $instance, $stage),
            WorkflowStageType::ActionPlan => Schema::hasTable('actions_correctives')
                && ActionCorrective::query()
                    ->whereHas('risque.actif.processus', fn ($query) => $query->where('mission_id', $mission->id))
                    ->exists(),
            WorkflowStageType::Reporting => Schema::hasTable('mission_documents')
                && MissionDocument::query()->where('mission_id', $mission->id)->where('category', 'report')->exists(),
            WorkflowStageType::Custom => $this->customStageSatisfied($instance, $stage, $executionMode),
            default => false,
        };
    }

    private function questionnaireStageSatisfied(Mission $mission, WorkflowStage $stage): bool
    {
        if (! Schema::hasTable('entretiens')) {
            return false;
        }

        return Entretien::query()
            ->where('mission_id', $mission->id)
            ->when(
                $stage->questionnaire_template_id !== null,
                fn ($query) => $query->where('questionnaire_template_id', $stage->questionnaire_template_id)
            )
            ->where(function ($query) {
                if (Schema::hasColumn('entretiens', 'status')) {
                    $query->whereIn('status', [Entretien::STATUS_COMPLETED, Entretien::STATUS_VALIDATED]);
                }

                if (Schema::hasTable('entretien_responses')) {
                    $query->orWhereHas('questionnaireResponses');
                }
            })
            ->exists();
    }

    private function formStageSatisfied(WorkflowInstance $instance, WorkflowStage $stage): bool
    {
        $execution = $instance->stageExecutions()
            ->where('workflow_stage_id', $stage->id)
            ->latest('id')
            ->first();

        if ($execution instanceof WorkflowStageExecution && in_array((string) $execution->status, ['completed', 'skipped'], true)) {
            return true;
        }

        $key = $stage->code;

        return filled(data_get($instance->metadata, 'forms.'.$key))
            || filled(data_get($instance->metadata, 'stage_payloads.'.$key));
    }

    private function riskCaptureSatisfied(Mission $mission): bool
    {
        if (Schema::hasTable('identified_risks')
            && IdentifiedRisk::query()->where('mission_id', $mission->id)->exists()) {
            return true;
        }

        if (! Schema::hasTable('risques')) {
            return false;
        }

        return Risque::query()
            ->whereHas('actif.processus', fn ($query) => $query->where('mission_id', $mission->id))
            ->exists();
    }

    private function approvalStageSatisfied(Mission $mission, WorkflowInstance $instance, WorkflowStage $stage): bool
    {
        if (in_array((string) ($mission->mission_status ?? ''), [
            Mission::STATUS_VALIDEE_IS,
            Mission::STATUS_VALIDEE_COPRI,
        ], true)) {
            return true;
        }

        $execution = $instance->stageExecutions()
            ->where('workflow_stage_id', $stage->id)
            ->latest('id')
            ->first();

        return (bool) data_get($execution?->payload ?? [], 'approved', false);
    }

    private function customStageSatisfied(
        WorkflowInstance $instance,
        WorkflowStage $stage,
        ?WorkflowExecutionMode $executionMode,
    ): bool {
        if ($executionMode === WorkflowExecutionMode::Automatic) {
            return true;
        }

        $execution = $instance->stageExecutions()
            ->where('workflow_stage_id', $stage->id)
            ->latest('id')
            ->first();

        return $execution instanceof WorkflowStageExecution
            && in_array((string) $execution->status, ['completed', 'skipped'], true);
    }
}
