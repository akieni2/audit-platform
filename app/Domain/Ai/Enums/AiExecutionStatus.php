<?php

namespace App\Domain\Ai\Enums;

enum AiExecutionStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Blocked = 'blocked';
    case Moderated = 'moderated';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Running => 'En cours',
            self::Completed => 'Terminé',
            self::Failed => 'Échec',
            self::Blocked => 'Bloqué',
            self::Moderated => 'Modéré',
        };
    }
}
