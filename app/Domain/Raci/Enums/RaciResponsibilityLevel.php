<?php

namespace App\Domain\Raci\Enums;

enum RaciResponsibilityLevel: string
{
    case Low = 'low';
    case Moderate = 'moderate';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Faible',
            self::Moderate => 'Modere',
            self::High => 'Eleve',
            self::Critical => 'Critique',
        };
    }

    public function score(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Moderate => 2,
            self::High => 3,
            self::Critical => 4,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];

        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->label();
        }

        return $labels;
    }
}
