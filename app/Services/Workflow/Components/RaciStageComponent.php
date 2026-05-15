<?php

namespace App\Services\Workflow\Components;

use App\Domain\Workflow\Enums\WorkflowStageType;
use App\Models\RaciAssignment;
use App\Models\RaciTemplate;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStage;
use App\Services\Raci\RaciAnalyticsService;
use App\Services\Raci\RaciAssignmentService;
use App\Services\Raci\RaciValidationService;
use App\Services\Workflow\Components\Contracts\WorkflowStageComponent;
use App\Services\Workflow\WorkflowExecutionService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class RaciStageComponent implements WorkflowStageComponent
{
    public function __construct(
        private RaciAssignmentService $assignments,
        private RaciValidationService $validations,
        private RaciAnalyticsService $analytics,
        private WorkflowExecutionService $execution,
    ) {}

    public function key(): string
    {
        return 'raci_stage';
    }

    public function aliases(): array
    {
        return [
            'raci_stage',
            'raci_assignment_grid',
            'raci_validation_form',
        ];
    }

    public function buildViewData(WorkflowInstance $instance, WorkflowStage $stage, ?User $actor = null): array
    {
        $instance->loadMissing('mission');

        return [
            'view' => 'workflows.runtime.components.raci-stage',
            'instance' => $instance,
            'stage' => $stage,
            'raciView' => $instance->mission
                ? $this->analytics->missionSnapshot($instance->mission, $stage->raciTemplate)
                : null,
            'selectedTemplate' => $stage->raciTemplate,
        ];
    }

    public function handleSubmission(Request $request, WorkflowInstance $instance, WorkflowStage $stage, ?User $actor = null): array
    {
        $instance->loadMissing('mission');
        $mission = $instance->mission;

        if ($mission === null) {
            throw new InvalidArgumentException('Mission introuvable pour le stage RACI.');
        }

        $this->execution->startStage($instance, $stage, $actor, ['component_key' => $stage->resolvedComponentKey()]);

        $payload = ['component_key' => $stage->resolvedComponentKey()];

        if ($stage->resolvedStageType() === WorkflowStageType::RaciAssignment) {
            $templateId = $request->integer('raci_template_id') ?: (int) $stage->raci_template_id;
            $template = RaciTemplate::query()->find($templateId);

            if (! $template instanceof RaciTemplate) {
                throw new InvalidArgumentException('Aucun template RACI n’est configuré pour ce stage.');
            }

            $matrix = $this->assignments->assignForMission($template, $mission, [
                'workflow_instance_id' => $instance->id,
                'status' => 'assigned',
                'process_label' => $request->input('process_label'),
                'assignments' => [[
                    'raci_role_id' => $request->integer('raci_role_id'),
                    'assigned_user_id' => $request->integer('assigned_user_id') ?: null,
                    'process_label' => $request->input('process_label'),
                    'role_type' => $request->input('role_type', 'responsible'),
                    'responsibility_level' => $request->input('responsibility_level', 'moderate'),
                    'notes' => $request->input('notes'),
                    'status' => 'assigned',
                ]],
            ], $actor);

            $payload['raci_matrix_id'] = $matrix->id;
        } else {
            $assignment = RaciAssignment::query()
                ->where('mission_id', $mission->id)
                ->latest('id')
                ->first();

            if (! $assignment instanceof RaciAssignment) {
                throw new InvalidArgumentException('Aucune affectation RACI precedente n’est disponible pour validation.');
            }

            $validation = $this->validations->record($assignment, [
                'status' => $request->input('status', 'approved'),
                'notes' => $request->input('notes'),
                'metadata' => ['workflow_stage_id' => $stage->id],
            ], $actor);

            $payload['raci_validation_id'] = $validation->id;
            $payload['approved'] = (($validation->status?->value) ?? $validation->getRawOriginal('status')) === 'approved';
        }

        $updatedInstance = $this->execution->completeStage(
            $instance->fresh(['currentStage', 'stageExecutions.workflowStage']),
            $stage,
            $actor,
            $payload,
            'Stage RACI complete.'
        );

        return [
            'instance' => $updatedInstance,
            'message' => 'Stage RACI enregistre.',
        ];
    }
}
