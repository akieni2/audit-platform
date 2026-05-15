<?php

namespace App\Services\Workflow;

use App\Domain\Risk\Enums\RiskLifecycleStatus;
use App\Domain\Workflow\Enums\WorkflowExecutionMode;
use App\Domain\Workflow\Enums\WorkflowInstanceStatus;
use App\Domain\Workflow\Enums\WorkflowStageExecutionStatus;
use App\Domain\Workflow\Enums\WorkflowStageType;
use App\Models\ActionCorrective;
use App\Models\Entretien;
use App\Models\IdentifiedRisk;
use App\Models\Mission;
use App\Models\MissionDocument;
use App\Models\MissionRaciPreview;
use App\Models\MissionRiskProjection;
use App\Models\MissionSwotPreview;
use App\Models\RaciMatrix;
use App\Models\RaciValidation;
use App\Models\Risque;
use App\Models\SwotAnalysis;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStage;
use App\Models\WorkflowStageExecution;
use App\Models\WorkflowTemplate;
use App\Models\WorkflowTransition;
use App\Services\Runtime\BusinessEventLogger;
use App\Services\Runtime\CoreTransactionRunner;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class WorkflowEngineService
{
    public function __construct(
        private CoreTransactionRunner $transactions,
        private BusinessEventLogger $events,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function start(
        Mission $mission,
        WorkflowTemplate $template,
        ?User $actor = null,
        array $metadata = [],
    ): WorkflowInstance {
        $template->loadMissing(['stages', 'transitions']);
        $existing = $mission->workflowInstance()->with([
            'workflowTemplate.stages',
            'workflowTemplate.transitions',
            'currentStage',
            'stageExecutions.workflowStage',
        ])->first();

        if ($existing !== null) {
            return $existing;
        }

        /** @var WorkflowStage|null $firstStage */
        $firstStage = $template->stages->sortBy('sort_order')->first();
        if ($firstStage === null) {
            throw new InvalidArgumentException('Le workflow ne contient aucune étape.');
        }

        $correlationId = $this->events->resolveCorrelationId([
            'mission_id' => $mission->id,
            'workflow_template_id' => $template->id,
        ]);

        /** @var WorkflowInstance $instance */
        $instance = $this->transactions->run(
            name: 'workflow.instance.start',
            context: [
                'mission_id' => $mission->id,
                'workflow_template_id' => $template->id,
                'correlation_id' => $correlationId,
            ],
            callback: function () use ($mission, $template, $actor, $metadata, $firstStage) {
                $instance = WorkflowInstance::query()->create([
                    'workflow_template_id' => $template->id,
                    'mission_id' => $mission->id,
                    'current_stage_id' => $firstStage->id,
                    'status' => WorkflowInstanceStatus::Running->value,
                    'started_at' => now(),
                    'created_by' => $actor?->id,
                    'metadata' => $metadata,
                ]);

                foreach ($template->stages as $stage) {
                    WorkflowStageExecution::query()->create([
                        'workflow_instance_id' => $instance->id,
                        'workflow_stage_id' => $stage->id,
                        'status' => $stage->is($firstStage)
                            ? WorkflowStageExecutionStatus::Active->value
                            : WorkflowStageExecutionStatus::Pending->value,
                        'started_at' => $stage->is($firstStage) ? now() : null,
                        'assigned_to' => $actor?->id,
                    ]);
                }

                if (Schema::hasColumn('missions', 'workflow_instance_id')) {
                    $mission->forceFill([
                        'workflow_instance_id' => $instance->id,
                    ])->save();
                }

                return $instance->fresh([
                    'workflowTemplate.stages',
                    'workflowTemplate.transitions',
                    'currentStage',
                    'stageExecutions.workflowStage',
                    'mission',
                ]);
            }
        );

        $this->events->record(
            eventName: 'workflow.instance.started',
            payload: [
                'workflow_instance_id' => $instance->id,
                'workflow_template_id' => $template->id,
                'current_stage_id' => $instance->current_stage_id,
            ],
            context: ['correlation_id' => $correlationId],
            aggregateType: 'workflow_instance',
            aggregateId: $instance->id,
            actor: $actor,
            missionId: $mission->id,
            correlationId: $correlationId,
            idempotencyKey: 'workflow-start:'.$mission->id.':'.$template->id,
        );

        return $this->synchronize($instance, $actor);
    }

    public function currentStage(WorkflowInstance $instance): ?WorkflowStage
    {
        $instance->loadMissing('currentStage');

        return $instance->currentStage;
    }

    /**
     * @return Collection<int, WorkflowTransition>
     */
    public function availableTransitions(WorkflowInstance $instance, ?User $actor = null): Collection
    {
        $instance->loadMissing('workflowTemplate.transitions.fromStage', 'workflowTemplate.transitions.toStage', 'currentStage', 'mission');

        if ($instance->currentStage === null) {
            return collect();
        }

        return $instance->workflowTemplate->transitions
            ->filter(fn (WorkflowTransition $transition) => (int) $transition->from_stage_id === (int) $instance->current_stage_id)
            ->filter(fn (WorkflowTransition $transition) => $this->verifyTransition($instance, $transition, $actor))
            ->values();
    }

    public function verifyTransition(WorkflowInstance $instance, WorkflowTransition $transition, ?User $actor = null): bool
    {
        if ((int) $transition->from_stage_id !== (int) $instance->current_stage_id) {
            return false;
        }

        if (! $this->matchesRoleRequirement($actor, $transition->role_required)) {
            return false;
        }

        return $this->matchesCondition($instance, $transition);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function advance(
        WorkflowInstance $instance,
        ?User $actor = null,
        ?WorkflowTransition $transition = null,
        array $payload = [],
        ?string $notes = null,
    ): WorkflowInstance {
        $instance->loadMissing([
            'workflowTemplate.transitions.toStage',
            'currentStage',
            'stageExecutions.workflowStage',
            'mission',
        ]);

        if ($this->isCompleted($instance)) {
            return $instance;
        }

        $currentStage = $instance->currentStage;
        if ($currentStage === null) {
            return $this->markCompleted($instance, $actor, $payload, $notes);
        }

        $transition ??= $this->availableTransitions($instance, $actor)
            ->sortBy(fn (WorkflowTransition $candidate) => $candidate->toStage?->sort_order ?? PHP_INT_MAX)
            ->first();

        if ($transition === null) {
            return $this->markCompleted($instance, $actor, $payload, $notes);
        }

        if (! $this->verifyTransition($instance, $transition, $actor)) {
            throw new InvalidArgumentException('Transition de workflow non autorisée.');
        }

        $correlationId = $this->events->resolveCorrelationId([
            'mission_id' => $instance->mission_id,
            'workflow_instance_id' => $instance->id,
            'from_stage_id' => $currentStage->id,
            'to_stage_id' => $transition->to_stage_id,
        ]);

        /** @var WorkflowInstance $fresh */
        $fresh = $this->transactions->run(
            name: 'workflow.instance.advance',
            context: [
                'mission_id' => $instance->mission_id,
                'workflow_instance_id' => $instance->id,
                'correlation_id' => $correlationId,
            ],
            callback: function () use ($instance, $actor, $payload, $notes, $transition, $currentStage) {
                $execution = WorkflowStageExecution::query()
                    ->where('workflow_instance_id', $instance->id)
                    ->where('workflow_stage_id', $currentStage->id)
                    ->latest('id')
                    ->first();

                if ($execution === null) {
                    $execution = WorkflowStageExecution::query()->create([
                        'workflow_instance_id' => $instance->id,
                        'workflow_stage_id' => $currentStage->id,
                        'status' => WorkflowStageExecutionStatus::Active->value,
                        'started_at' => now(),
                        'assigned_to' => $actor?->id,
                    ]);
                }

                $execution->fill([
                    'status' => WorkflowStageExecutionStatus::Completed->value,
                    'completed_at' => now(),
                    'assigned_to' => $actor?->id ?? $execution->assigned_to,
                    'payload' => $this->mergePayload($execution->payload, $payload),
                    'notes' => $notes ?? $execution->notes,
                ])->save();

                $nextStageId = $transition->to_stage_id;
                $instance->forceFill([
                    'current_stage_id' => $nextStageId,
                    'status' => WorkflowInstanceStatus::Running->value,
                ])->save();

                $nextExecution = WorkflowStageExecution::query()
                    ->where('workflow_instance_id', $instance->id)
                    ->where('workflow_stage_id', $nextStageId)
                    ->whereIn('status', [
                        WorkflowStageExecutionStatus::Pending->value,
                        WorkflowStageExecutionStatus::Rejected->value,
                    ])
                    ->latest('id')
                    ->first();

                if ($nextExecution === null) {
                    $nextExecution = WorkflowStageExecution::query()->create([
                        'workflow_instance_id' => $instance->id,
                        'workflow_stage_id' => $nextStageId,
                    ]);
                }

                $nextExecution->fill([
                    'status' => WorkflowStageExecutionStatus::Active->value,
                    'started_at' => $nextExecution->started_at ?? now(),
                    'assigned_to' => $actor?->id ?? $nextExecution->assigned_to,
                ])->save();

                return $instance->fresh([
                    'workflowTemplate.transitions.toStage',
                    'currentStage',
                    'stageExecutions.workflowStage',
                    'mission',
                ]);
            }
        );

        $this->events->record(
            eventName: 'workflow.instance.advanced',
            payload: [
                'workflow_instance_id' => $fresh->id,
                'from_stage_id' => $currentStage->id,
                'to_stage_id' => $transition->to_stage_id,
                'payload' => $payload,
            ],
            context: ['correlation_id' => $correlationId],
            aggregateType: 'workflow_instance',
            aggregateId: $fresh->id,
            actor: $actor,
            missionId: $fresh->mission_id,
            correlationId: $correlationId,
            idempotencyKey: 'workflow-advance:'.$fresh->id.':'.$currentStage->id.':'.$transition->to_stage_id.':'.sha1(json_encode($payload) ?: ''),
        );

        return $fresh;
    }

    public function isCompleted(WorkflowInstance $instance): bool
    {
        $status = $instance->status instanceof WorkflowInstanceStatus
            ? $instance->status
            : WorkflowInstanceStatus::from((string) $instance->status);

        return $status === WorkflowInstanceStatus::Completed || $instance->current_stage_id === null;
    }

    public function synchronize(WorkflowInstance $instance, ?User $actor = null): WorkflowInstance
    {
        $instance->loadMissing([
            'workflowTemplate.stages',
            'workflowTemplate.transitions.toStage',
            'currentStage',
            'stageExecutions.workflowStage',
            'mission',
        ]);

        $guard = 0;
        $current = $instance;

        while (! $this->isCompleted($current) && $current->currentStage !== null && $this->stageIsSatisfied($current, $current->currentStage)) {
            $transition = $this->availableTransitions($current, $actor)
                ->sortBy(fn (WorkflowTransition $candidate) => $candidate->toStage?->sort_order ?? PHP_INT_MAX)
                ->first();

            if ($transition === null) {
                $current = $this->markCompleted(
                    $current,
                    $actor,
                    ['auto_completed' => true],
                    'Workflow mission complété automatiquement.'
                );
                break;
            }

            $current = $this->advance(
                $current,
                $actor,
                $transition,
                [
                    'auto_completed' => true,
                    'stage_code' => $current->currentStage->code,
                    'synchronized_at' => now()->toIso8601String(),
                ],
                'Synchronisation automatique du workflow depuis les composants core.'
            );

            $guard++;
            if ($guard > 25) {
                break;
            }
        }

        return $current->fresh([
            'workflowTemplate.stages',
            'currentStage',
            'stageExecutions.workflowStage',
            'mission',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function markCompleted(
        WorkflowInstance $instance,
        ?User $actor = null,
        array $payload = [],
        ?string $notes = null,
    ): WorkflowInstance {
        $instance->loadMissing('currentStage', 'stageExecutions.workflowStage', 'mission');

        $correlationId = $this->events->resolveCorrelationId([
            'mission_id' => $instance->mission_id,
            'workflow_instance_id' => $instance->id,
        ]);

        /** @var WorkflowInstance $fresh */
        $fresh = $this->transactions->run(
            name: 'workflow.instance.complete',
            context: [
                'mission_id' => $instance->mission_id,
                'workflow_instance_id' => $instance->id,
                'correlation_id' => $correlationId,
            ],
            callback: function () use ($instance, $actor, $payload, $notes) {
                if ($instance->current_stage_id !== null) {
                    $execution = WorkflowStageExecution::query()
                        ->where('workflow_instance_id', $instance->id)
                        ->where('workflow_stage_id', $instance->current_stage_id)
                        ->latest('id')
                        ->first();

                    if ($execution !== null && $execution->status === WorkflowStageExecutionStatus::Active) {
                        $execution->fill([
                            'status' => WorkflowStageExecutionStatus::Completed->value,
                            'completed_at' => now(),
                            'assigned_to' => $actor?->id ?? $execution->assigned_to,
                            'payload' => $this->mergePayload($execution->payload, $payload),
                            'notes' => $notes ?? $execution->notes,
                        ])->save();
                    }
                }

                $instance->forceFill([
                    'current_stage_id' => null,
                    'status' => WorkflowInstanceStatus::Completed->value,
                    'completed_at' => now(),
                ])->save();

                return $instance->fresh([
                    'workflowTemplate.stages',
                    'stageExecutions.workflowStage',
                    'mission',
                ]);
            }
        );

        $this->events->record(
            eventName: 'workflow.instance.completed',
            payload: [
                'workflow_instance_id' => $fresh->id,
                'payload' => $payload,
            ],
            context: ['correlation_id' => $correlationId],
            aggregateType: 'workflow_instance',
            aggregateId: $fresh->id,
            actor: $actor,
            missionId: $fresh->mission_id,
            correlationId: $correlationId,
            idempotencyKey: 'workflow-complete:'.$fresh->id,
        );

        return $fresh;
    }

    private function matchesRoleRequirement(?User $actor, ?string $roleRequired): bool
    {
        if ($roleRequired === null || trim($roleRequired) === '') {
            return true;
        }

        if ($actor === null) {
            return false;
        }

        $actor->loadMissing('institutionalRole');
        $required = trim($roleRequired);

        if (str_starts_with($required, 'permission:')) {
            return $actor->hasPermission(substr($required, strlen('permission:')));
        }

        if (str_starts_with($required, 'institutional:')) {
            return $actor->institutionalRole?->slug === substr($required, strlen('institutional:'));
        }

        if (str_starts_with($required, 'role:')) {
            return ($actor->role ?? null) === substr($required, strlen('role:'));
        }

        return $actor->institutionalRole?->slug === $required
            || ($actor->role ?? null) === $required
            || $actor->hasPermission($required);
    }

    private function matchesCondition(WorkflowInstance $instance, WorkflowTransition $transition): bool
    {
        $conditionType = trim((string) ($transition->condition_type ?? ''));
        $configuration = $transition->condition_configuration ?? [];

        if ($conditionType === '') {
            return true;
        }

        $instance->loadMissing('mission');

        return match ($conditionType) {
            'mission_status' => ($instance->mission?->mission_status ?? null) === ($configuration['status'] ?? null),
            'mission_field_not_null' => filled(data_get($instance->mission, (string) ($configuration['field'] ?? ''))),
            'metadata_value' => data_get($instance->metadata, (string) ($configuration['key'] ?? '')) === ($configuration['value'] ?? null),
            default => false,
        };
    }

    private function stageIsSatisfied(WorkflowInstance $instance, WorkflowStage $stage): bool
    {
        $instance->loadMissing('mission');
        $mission = $instance->mission;

        if ($mission === null) {
            return false;
        }

        $stageType = $stage->resolvedStageType();
        $executionMode = $stage->resolvedExecutionMode();

        return match ($stageType) {
            WorkflowStageType::Mission => filled($mission->organisation) && filled($mission->date_debut),
            WorkflowStageType::ServiceSelection => Schema::hasTable('services')
                && $mission->services()->exists(),
            WorkflowStageType::Questionnaire => Schema::hasTable('entretiens')
                && Entretien::query()
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
                    ->exists(),
            WorkflowStageType::Form => filled(data_get($instance->metadata, 'forms.'.$stage->code))
                || filled(data_get($instance->metadata, 'stage_payloads.'.$stage->code)),
            WorkflowStageType::RiskCapture => Schema::hasTable('identified_risks')
                && IdentifiedRisk::query()->where('mission_id', $mission->id)->exists(),
            WorkflowStageType::Heatmap => Schema::hasTable('mission_risk_projections')
                && MissionRiskProjection::query()->where('mission_id', $mission->id)->exists(),
            WorkflowStageType::DocumentReview => Schema::hasTable('mission_documents')
                && MissionDocument::query()->where('mission_id', $mission->id)->exists(),
            WorkflowStageType::SwotAnalysis => $this->swotAnalysisSatisfied($mission, $stage),
            WorkflowStageType::SwotValidation => $this->swotValidationSatisfied($mission, $instance, $stage),
            WorkflowStageType::RaciAssignment => $this->raciAssignmentSatisfied($mission, $stage),
            WorkflowStageType::RaciValidation => $this->raciValidationSatisfied($mission, $instance, $stage),
            WorkflowStageType::ActionPlan => Schema::hasTable('actions_correctives')
                && ActionCorrective::query()
                    ->whereHas('risque.actif.processus', fn ($query) => $query->where('mission_id', $mission->id))
                    ->exists(),
            WorkflowStageType::Reporting => Schema::hasTable('mission_documents')
                && MissionDocument::query()
                    ->where('mission_id', $mission->id)
                    ->where('category', 'report')
                    ->exists(),
            WorkflowStageType::Approval => in_array((string) ($mission->mission_status ?? ''), [
                Mission::STATUS_VALIDEE_IS,
                Mission::STATUS_VALIDEE_COPRI,
            ], true),
            WorkflowStageType::Custom => $executionMode === WorkflowExecutionMode::Automatic
                || (Schema::hasTable('identified_risks')
                    && IdentifiedRisk::query()
                        ->where('mission_id', $mission->id)
                        ->whereIn('lifecycle_status', [
                            RiskLifecycleStatus::UnderReview->value,
                            RiskLifecycleStatus::Validated->value,
                            RiskLifecycleStatus::Promoted->value,
                        ])
                        ->exists())
                || $this->officialRisksExist($mission),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>|null  $base
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mergePayload(?array $base, array $payload): array
    {
        return array_replace_recursive($base ?? [], $payload);
    }

    private function officialRisksExist(Mission $mission): bool
    {
        if (! Schema::hasTable('risques')) {
            return false;
        }

        return Risque::query()
            ->whereHas('actif.processus', fn ($query) => $query->where('mission_id', $mission->id))
            ->exists();
    }

    private function swotAnalysisSatisfied(Mission $mission, WorkflowStage $stage): bool
    {
        if (Schema::hasTable('swot_analyses')
            && SwotAnalysis::query()
                ->where('mission_id', $mission->id)
                ->when($stage->swot_template_id !== null, fn ($query) => $query->where('swot_template_id', $stage->swot_template_id))
                ->whereIn('status', ['completed', 'validated'])
                ->exists()) {
            return true;
        }

        return Schema::hasTable('mission_swot_previews')
            && MissionSwotPreview::query()
                ->where('mission_id', $mission->id)
                ->where('status', '!=', 'placeholder')
                ->exists();
    }

    private function swotValidationSatisfied(Mission $mission, WorkflowInstance $instance, WorkflowStage $stage): bool
    {
        if (Schema::hasTable('swot_analyses')
            && SwotAnalysis::query()
                ->where('mission_id', $mission->id)
                ->when($stage->swot_template_id !== null, fn ($query) => $query->where('swot_template_id', $stage->swot_template_id))
                ->whereIn('status', ['validated', 'approved'])
                ->exists()) {
            return true;
        }

        $execution = $instance->stageExecutions()
            ->where('workflow_stage_id', $stage->id)
            ->latest('id')
            ->first();

        return (bool) data_get($execution?->payload ?? [], 'approved', false);
    }

    private function raciAssignmentSatisfied(Mission $mission, WorkflowStage $stage): bool
    {
        if (Schema::hasTable('raci_matrices')
            && RaciMatrix::query()
                ->where('mission_id', $mission->id)
                ->when($stage->raci_template_id !== null, fn ($query) => $query->where('raci_template_id', $stage->raci_template_id))
                ->where('status', '!=', 'draft')
                ->exists()) {
            return true;
        }

        return Schema::hasTable('mission_raci_previews')
            && MissionRaciPreview::query()
                ->where('mission_id', $mission->id)
                ->where('status', '!=', 'placeholder')
                ->exists();
    }

    private function raciValidationSatisfied(Mission $mission, WorkflowInstance $instance, WorkflowStage $stage): bool
    {
        if (Schema::hasTable('raci_validations')
            && RaciValidation::query()
                ->where('mission_id', $mission->id)
                ->where('status', 'approved')
                ->exists()) {
            return true;
        }

        $execution = $instance->stageExecutions()
            ->where('workflow_stage_id', $stage->id)
            ->latest('id')
            ->first();

        return (bool) data_get($execution?->payload ?? [], 'approved', false);
    }
}
