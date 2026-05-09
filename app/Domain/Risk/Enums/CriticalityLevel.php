<?php

namespace App\Domain\Risk\Enums;

enum CriticalityLevel: string
{
    case Faible = 'faible';
    case Moyen = 'moyen';
    case Eleve = 'eleve';
    case Critique = 'critique';

    public function label(): string
    {
        return match ($this) {
            self::Faible => 'Faible',
            self::Moyen => 'Moyen',
            self::Eleve => 'Élevé',
            self::Critique => 'Critique',
        };
    }

    public function isCritique(): bool
    {
        return $this === self::Critique;
    }
}
