<?php

namespace App\Services\Workflow\Components;

use App\Models\Entretien;
use App\Models\IdentifiedRisk;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStage;
use App\Services\Forms\DynamicFormRendererService;
use App\Services\Missions\MissionGovernanceService;
use App\Services\Risk\RiskRegistryPromotionService;
use App\Services\Workflow\Components\Contracts\WorkflowStageComponent;
use App\Services\Workflow\WorkflowExecutionService;
use Illuminate\Http\Request;

class DynamicFormStageComponent implements WorkflowStageComponent
{
    public function __construct(
        private DynamicFormRendererService $renderer,
        private WorkflowExecutionService $execution,
        private MissionGovernanceService $missionGovernance,
        private RiskRegistryPromotionService $riskRegistry,
    ) {}

    public function key(): string
    {
        return 'dynamic_form';
    }

    public function aliases(): array
    {
        return [
            'dynamic_form',
            'dynamic_interview_form',
            'approval_form',
            'risk_capture_form',
        ];
    }

    public function buildViewData(WorkflowInstance $instance, WorkflowStage $stage, ?User $actor = null): array
    {
        $entretien = $this->resolveEntretien($instance, $stage);

        return [
            'view' => 'workflows.runtime.components.dynamic-form',
            'stage' => $stage,
            'instance' => $instance,
            'entretien' => $entretien,
            'form' => $this->renderer->buildViewData($instance, $stage, $entretien),
        ];
    }

    public function handleSubmission(Request $request, WorkflowInstance $instance, WorkflowStage $stage, ?User $actor = null): array
    {
        $entretien = $this->resolveEntretien($instance, $stage, createIfMissing: true);
        $execution = $this->execution->startStage(
            $instance,
            $stage,
            $actor,
            ['component_key' => $stage->resolvedComponentKey()],
            'Stage dynamique initialisé.'
        );

        $result = $this->renderer->persistSubmission(
            $request,
            $instance,
            $stage,
            $execution,
            $actor,
            $entretien,
        );

        if (! $result['finalized']) {
            return [
                ...$result,
                'instance' => $instance->fresh(['currentStage', 'stageExecutions.workflowStage', 'formSubmissions']),
                'message' => 'Brouillon enregistré.',
            ];
        }

        $this->bridgeMissionGovernance($instance, $stage, $actor, $result['payload']);
        $this->bridgeRiskSelectors($stage, $actor, $result['payload']);

        $updatedInstance = $this->execution->completeStage(
            $instance->fresh(['currentStage', 'stageExecutions.workflowStage', 'formSubmissions']),
            $stage,
            $actor,
            $result['payload'],
            'Formulaire dynamique complété.'
        );

        return [
            ...$result,
            'instance' => $updatedInstance,
            'message' => 'Étape dynamique complétée.',
        ];
    }

    private function resolveEntretien(WorkflowInstance $instance, WorkflowStage $stage, bool $createIfMissing = false): ?Entretien
    {
        if ($stage->resolvedComponentKey() !== 'dynamic_interview_form') {
            return null;
        }

        if (! $instance->relationLoaded('mission')) {
            $instance->loadMissing('mission.services');
        }

        $mission = $instance->mission;
        if ($mission === null) {
            return null;
        }

        $entretien = Entretien::query()
            ->where('mission_id', $mission->id)
            ->latest('id')
            ->first();

        if ($entretien instanceof Entretien || ! $createIfMissing) {
            return $entretien;
        }

        $service = $mission->services()->orderBy('id')->first();
        if ($service === null) {
            return null;
        }

        return Entretien::query()->create([
            'mission_id' => $mission->id,
            'service_id' => $service->id,
            'status' => Entretien::STATUS_DRAFT,
            'date_entretien' => now()->toDateString(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function bridgeMissionGovernance(WorkflowInstance $instance, WorkflowStage $stage, ?User $actor, array $payload): void
    {
        $action = (string) data_get($stage->resolvedConfiguration(), 'mission_transition_action', '');
        if ($action === '' || ! $actor instanceof User) {
            return;
        }

        $instance->loadMissing('mission');
        if ($instance->mission === null) {
            return;
        }

        $comment = (string) data_get($payload, 'fields.governance_comment', '');
        $this->missionGovernance->transition($actor, $instance->mission, $action, $comment !== '' ? $comment : null);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function bridgeRiskSelectors(WorkflowStage $stage, ?User $actor, array $payload): void
    {
        $action = (string) data_get($stage->resolvedConfiguration(), 'risk_selector_action', '');
        if ($action === '' || ! $actor instanceof User) {
            return;
        }

        $snapshot = $this->renderer->resolveSnapshot($stage);
        foreach (($snapshot['template']['fields'] ?? []) as $field) {
            if (($field['field_type'] ?? null) !== \App\Models\FormField::TYPE_RISK_SELECTOR) {
                continue;
            }

            $riskIds = array_values(array_filter(array_map('intval', \Illuminate\Support\Arr::wrap(data_get($payload, 'fields.'.$field['field_key'])))));
            if ($riskIds === []) {
                continue;
            }

            $risks = IdentifiedRisk::query()->whereIn('id', $riskIds)->get();
            foreach ($risks as $risk) {
                match ($action) {
                    'submit_for_review' => $this->riskRegistry->submitForReview($risk, $actor),
                    'approve' => $this->riskRegistry->approve($risk, $actor),
                    'promote' => $this->riskRegistry->promote($risk, $actor),
                    default => null,
                };
            }
        }
    }
}
