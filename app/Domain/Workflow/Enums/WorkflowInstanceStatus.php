<?php

namespace App\Domain\Workflow\Enums;

enum WorkflowInstanceStatus: string
{
    case Draft = 'draft';
    case Running = 'running';
    case Paused = 'paused';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Running => 'En cours',
            self::Paused => 'En pause',
            self::Completed => 'Complété',
            self::Cancelled => 'Annulé',
        };
    }
}
