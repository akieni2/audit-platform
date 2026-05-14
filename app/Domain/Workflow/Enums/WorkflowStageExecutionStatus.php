<?php

namespace App\Domain\Workflow\Enums;

enum WorkflowStageExecutionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Completed = 'completed';
    case Skipped = 'skipped';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Active => 'Active',
            self::Completed => 'Complétée',
            self::Skipped => 'Ignorée',
            self::Rejected => 'Rejetée',
        };
    }
}
