<?php

namespace App\Services\Workflow;

use App\Models\WorkflowStage;
use App\Models\WorkflowTemplate;
use App\Models\WorkflowTransition;
use App\ViewModels\Workflow\WorkflowNodeViewModel;
use App\ViewModels\Workflow\WorkflowTransitionViewModel;
use Illuminate\Support\Collection;

class WorkflowCanvasService
{
    public function __construct(
        private WorkflowGraphLayoutService $layout,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(WorkflowTemplate $template, ?WorkflowStage $selectedStage = null): array
    {
        $template->loadMissing([
            'stages.formTemplate',
            'stages.questionnaireTemplate',
            'stages.approvalRole',
            'transitions.fromStage',
            'transitions.toStage',
        ]);

        $nodes = $template->stages
            ->sortBy('sort_order')
            ->values()
            ->map(function (WorkflowStage $stage) use ($selectedStage) {
                return WorkflowNodeViewModel::fromStage(
                    $stage,
                    $selectedStage?->is($stage) ?? false,
                    $this->layout->laneForStage($stage),
                )->toArray();
            });

        $transitions = $template->transitions
            ->values()
            ->map(function (WorkflowTransition $transition) {
                return WorkflowTransitionViewModel::fromTransition(
                    $transition,
                    isValid: $transition->from_stage_id !== $transition->to_stage_id && $transition->fromStage !== null && $transition->toStage !== null,
                    validationMessages: $this->transitionMessages($transition),
                )->toArray();
            });

        return [
            'nodes' => $nodes->all(),
            'transitions' => $transitions->all(),
            'layout' => $this->layout->describe($template, $nodes),
            'stats' => [
                'nodes' => $nodes->count(),
                'transitions' => $transitions->count(),
                'invalid_transitions' => $transitions->where('is_valid', false)->count(),
                'approval_nodes' => $nodes->where('requires_approval', true)->count(),
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function transitionMessages(WorkflowTransition $transition): array
    {
        $messages = [];

        if ($transition->from_stage_id === $transition->to_stage_id) {
            $messages[] = 'Une transition ne peut pas pointer vers la même étape.';
        }

        if (! $transition->fromStage || ! $transition->toStage) {
            $messages[] = 'La transition référence une étape introuvable.';
        }

        if ($transition->condition_type === null && ! $transition->is_automatic) {
            $messages[] = 'Transition manuelle sans condition explicite.';
        }

        return $messages;
    }
}
