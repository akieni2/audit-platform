<?php

namespace App\Domain\Risk\Enums;

enum CriticalityLevel: string
{
    case Faible = 'faible';
    case Moyen = 'moyen';
    case Eleve = 'eleve';
    case Critique = 'critique';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Faible->value => self::Faible->label(),
            self::Moyen->value => self::Moyen->label(),
            self::Eleve->value => self::Eleve->label(),
            self::Critique->value => self::Critique->label(),
        ];
    }

    public static function fromMixed(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim($value));
        $normalized = str_replace(
            ['é', 'è', 'ê', 'ë', 'à', 'â', 'ä', 'î', 'ï', 'ô', 'ö', 'ù', 'û', 'ü'],
            ['e', 'e', 'e', 'e', 'a', 'a', 'a', 'i', 'i', 'o', 'o', 'u', 'u', 'u'],
            $normalized
        );

        return match ($normalized) {
            '', 'n/a', 'na', 'none' => null,
            'faible', 'bas', 'basse', 'low' => self::Faible,
            'moyen', 'moyenne', 'medium', 'moderate' => self::Moyen,
            'eleve', 'elevee', 'high' => self::Eleve,
            'critique', 'critical' => self::Critique,
            default => self::tryFrom($normalized),
        };
    }

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
