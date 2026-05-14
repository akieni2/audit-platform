<?php

namespace App\Services\Workflow;

use App\Models\WorkflowInstance;
use App\Models\WorkflowStage;

class WorkflowVisualStateService
{
    public function __construct(
        private WorkflowVisualStateResolver $resolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function resolve(WorkflowInstance $instance, WorkflowStage $stage): array
    {
        $state = $this->resolver->forStage($instance, $stage);

        return [
            'value' => $state->value,
            'label' => $state->label(),
            'badge_classes' => $state->badgeClasses(),
            'card_classes' => $state->cardClasses(),
            'accent_color' => $state->accentColor(),
        ];
    }
}
