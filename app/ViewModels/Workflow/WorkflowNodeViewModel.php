<?php

namespace App\ViewModels\Workflow;

use App\Models\WorkflowStage;

final class WorkflowNodeViewModel
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $code,
        public readonly string $stageType,
        public readonly string $executionMode,
        public readonly string $componentKey,
        public readonly int $x,
        public readonly int $y,
        public readonly ?string $color,
        public readonly ?string $icon,
        public readonly bool $isSelected,
        public readonly bool $requiresApproval,
        public readonly bool $allowSkip,
        public readonly string $lane,
        public readonly array $badges = [],
    ) {}

    public static function fromStage(WorkflowStage $stage, bool $isSelected = false, string $lane = 'default'): self
    {
        $stageType = $stage->resolvedStageType()?->label() ?? 'Stage';
        $executionMode = $stage->resolvedExecutionMode()?->label() ?? '—';
        $componentKey = $stage->resolvedComponentKey();
        $badges = array_values(array_filter([
            $stage->usesFormTemplate() ? 'Form' : null,
            $stage->usesQuestionnaire() ? 'Questionnaire' : null,
                    $stage->swot_template_id !== null ? 'SWOT' : null,
                    $stage->raci_template_id !== null ? 'RACI' : null,
            $stage->requires_approval ? 'Approval' : null,
            $stage->allow_skip ? 'Skip' : null,
        ]));

        return new self(
            id: (int) $stage->id,
            name: (string) $stage->name,
            code: (string) $stage->code,
            stageType: $stageType,
            executionMode: $executionMode,
            componentKey: $componentKey,
            x: (int) ($stage->position_x ?? 0),
            y: (int) ($stage->position_y ?? 0),
            color: $stage->color,
            icon: $stage->icon,
            isSelected: $isSelected,
            requiresApproval: (bool) $stage->requires_approval,
            allowSkip: (bool) $stage->allow_skip,
            lane: $lane,
            badges: $badges,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'stage_type' => $this->stageType,
            'execution_mode' => $this->executionMode,
            'component_key' => $this->componentKey,
            'x' => $this->x,
            'y' => $this->y,
            'color' => $this->color,
            'icon' => $this->icon,
            'is_selected' => $this->isSelected,
            'requires_approval' => $this->requiresApproval,
            'allow_skip' => $this->allowSkip,
            'lane' => $this->lane,
            'badges' => $this->badges,
        ];
    }
}
