<?php

namespace App\Domain\Workflow\Enums;

enum WorkflowExecutionMode: string
{
    case Manual = 'manual';
    case Automatic = 'automatic';
    case Questionnaire = 'questionnaire';
    case Approval = 'approval';
    case Form = 'form';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manuel',
            self::Automatic => 'Automatique',
            self::Questionnaire => 'Questionnaire',
            self::Approval => 'Approbation',
            self::Form => 'Formulaire',
            self::Custom => 'Personnalisé',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];

        foreach (self::cases() as $mode) {
            $labels[$mode->value] = $mode->label();
        }

        return $labels;
    }

    public static function fromMixed(string|null $value): ?self
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim($value));
        $normalized = str_replace([' ', '-'], '_', $normalized);

        return match ($normalized) {
            'manual' => self::Manual,
            'automatic', 'auto' => self::Automatic,
            'questionnaire', 'entretien' => self::Questionnaire,
            'approval', 'validation' => self::Approval,
            'form', 'formulaire' => self::Form,
            'custom', 'personnalise', 'personnalisé' => self::Custom,
            default => self::tryFrom($normalized),
        };
    }
}
