<?php

namespace App\Services\Workflow\Components;

use App\Domain\Workflow\Enums\WorkflowStageType;
use App\Models\SwotAnalysis;
use App\Models\SwotTemplate;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStage;
use App\Services\Swot\SwotAuditService;
use App\Services\Swot\SwotAnalyticsService;
use App\Services\Workflow\Components\Contracts\WorkflowStageComponent;
use App\Services\Workflow\WorkflowExecutionService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class SwotStageComponent implements WorkflowStageComponent
{
    public function __construct(
        private SwotAnalyticsService $analytics,
        private SwotAuditService $audit,
        private WorkflowExecutionService $execution,
    ) {}

    public function key(): string
    {
        return 'swot_stage';
    }

    public function aliases(): array
    {
        return [
            'swot_stage',
            'swot_analysis_form',
            'swot_validation_form',
        ];
    }

    public function buildViewData(WorkflowInstance $instance, WorkflowStage $stage, ?User $actor = null): array
    {
        $instance->loadMissing('mission');

        return [
            'view' => 'workflows.runtime.components.swot-stage',
            'instance' => $instance,
            'stage' => $stage,
            'swotView' => $instance->mission
                ? $this->analytics->missionSnapshot($instance->mission, $stage->swotTemplate)
                : null,
            'selectedTemplate' => $stage->swotTemplate,
        ];
    }

    public function handleSubmission(Request $request, WorkflowInstance $instance, WorkflowStage $stage, ?User $actor = null): array
    {
        $instance->loadMissing('mission');
        $mission = $instance->mission;

        if ($mission === null) {
            throw new InvalidArgumentException('Mission introuvable pour le stage SWOT.');
        }

        $this->execution->startStage($instance, $stage, $actor, ['component_key' => $stage->resolvedComponentKey()]);

        $payload = ['component_key' => $stage->resolvedComponentKey()];

        if ($stage->resolvedStageType() === WorkflowStageType::SwotAnalysis) {
            $templateId = $request->integer('swot_template_id') ?: (int) $stage->swot_template_id;
            $template = SwotTemplate::query()->find($templateId);

            if (! $template instanceof SwotTemplate) {
                throw new InvalidArgumentException('Aucun template SWOT n’est configuré pour ce stage.');
            }

            $analysis = $this->analytics->runMissionAnalysis($template, $mission, [
                'actor_id' => $actor?->id,
                'workflow_instance_id' => $instance->id,
                'notes' => $request->input('notes'),
            ]);

            $payload['swot_analysis_id'] = $analysis->id;
        } else {
            $analysis = SwotAnalysis::query()
                ->where('mission_id', $mission->id)
                ->latest('id')
                ->first();

            if (! $analysis instanceof SwotAnalysis) {
                throw new InvalidArgumentException('Aucune analyse SWOT precedente n’est disponible pour validation.');
            }

            $analysis->forceFill([
                'status' => 'validated',
                'updated_by' => $actor?->id,
                'concluded_at' => now(),
            ])->save();

            $this->audit->log(
                eventName: 'swot.analysis.validated',
                analysis: $analysis,
                actor: $actor,
                status: 'validated',
                payload: ['workflow_stage_id' => $stage->id],
            );

            $payload['swot_analysis_id'] = $analysis->id;
            $payload['approved'] = true;
        }

        $updatedInstance = $this->execution->completeStage(
            $instance->fresh(['currentStage', 'stageExecutions.workflowStage']),
            $stage,
            $actor,
            $payload,
            'Stage SWOT complete.'
        );

        return [
            'instance' => $updatedInstance,
            'message' => 'Stage SWOT enregistre.',
        ];
    }
}
