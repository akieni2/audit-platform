<?php

namespace App\ViewModels\Workflow;

use App\Models\WorkflowTransition;

final class WorkflowTransitionViewModel
{
    public function __construct(
        public readonly int $id,
        public readonly int $fromStageId,
        public readonly int $toStageId,
        public readonly string $fromLabel,
        public readonly string $toLabel,
        public readonly bool $isAutomatic,
        public readonly ?string $conditionType,
        public readonly ?string $roleRequired,
        public readonly bool $isValid,
        public readonly array $validationMessages = [],
    ) {}

    public static function fromTransition(WorkflowTransition $transition, bool $isValid = true, array $validationMessages = []): self
    {
        return new self(
            id: (int) $transition->id,
            fromStageId: (int) $transition->from_stage_id,
            toStageId: (int) $transition->to_stage_id,
            fromLabel: (string) ($transition->fromStage?->name ?? '—'),
            toLabel: (string) ($transition->toStage?->name ?? '—'),
            isAutomatic: (bool) $transition->is_automatic,
            conditionType: $transition->condition_type,
            roleRequired: $transition->role_required,
            isValid: $isValid,
            validationMessages: $validationMessages,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'from_stage_id' => $this->fromStageId,
            'to_stage_id' => $this->toStageId,
            'from_label' => $this->fromLabel,
            'to_label' => $this->toLabel,
            'is_automatic' => $this->isAutomatic,
            'condition_type' => $this->conditionType,
            'role_required' => $this->roleRequired,
            'is_valid' => $this->isValid,
            'validation_messages' => $this->validationMessages,
        ];
    }
}
