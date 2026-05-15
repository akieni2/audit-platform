<?php

namespace App\Domain\Ai\Enums;

enum AiContextType: string
{
    case Mission = 'mission';
    case Entretien = 'entretien';
    case Questionnaire = 'questionnaire';
    case Form = 'form';
    case Workflow = 'workflow';
    case Risk = 'risk';
    case Swot = 'swot';
    case Raci = 'raci';
    case Executive = 'executive';
    case InternalControl = 'internal_control';

    public function label(): string
    {
        return match ($this) {
            self::Mission => 'Mission',
            self::Entretien => 'Entretien',
            self::Questionnaire => 'Questionnaire',
            self::Form => 'Formulaire',
            self::Workflow => 'Workflow',
            self::Risk => 'Risque',
            self::Swot => 'SWOT',
            self::Raci => 'RACI',
            self::Executive => 'Exécutif',
            self::InternalControl => 'Contrôle interne',
        };
    }
}
