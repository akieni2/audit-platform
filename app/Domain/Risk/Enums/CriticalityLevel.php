<?php

namespace App\Domain\Risk\Enums;

enum CriticalityLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Low->value => self::Low->label(),
            self::Medium->value => self::Medium->label(),
            self::High->value => self::High->label(),
            self::Critical->value => self::Critical->label(),
        ];
    }

    public static function fromMixed(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($value));
        $normalized = str_replace(
            ['é', 'è', 'ê', 'ë', 'à', 'â', 'ä', 'î', 'ï', 'ô', 'ö', 'ù', 'û', 'ü', 'Ã©', 'Ã¨', 'Ãª', 'Ã«', 'Ã ', 'Ã¢', 'Ã¤', 'Ã®', 'Ã¯', 'Ã´', 'Ã¶', 'Ã¹', 'Ã»', 'Ã¼'],
            ['e', 'e', 'e', 'e', 'a', 'a', 'a', 'i', 'i', 'o', 'o', 'u', 'u', 'u', 'e', 'e', 'e', 'e', 'a', 'a', 'a', 'i', 'i', 'o', 'o', 'u', 'u', 'u'],
            $normalized
        );

        return match ($normalized) {
            '', 'n/a', 'na', 'none' => null,
            'faible', 'bas', 'basse', 'low' => self::Low,
            'moyen', 'moyenne', 'medium', 'moderate' => self::Medium,
            'eleve', 'elevee', 'haute', 'haut', 'high' => self::High,
            'critique', 'critical', 'severe', 'very_high', 'very-high' => self::Critical,
            default => self::tryFrom($normalized),
        };
    }

    public static function legacyMap(): array
    {
        return [
            'faible' => self::Low->value,
            'moyen' => self::Medium->value,
            'eleve' => self::High->value,
            'critique' => self::Critical->value,
            'low' => self::Low->value,
            'medium' => self::Medium->value,
            'high' => self::High->value,
            'critical' => self::Critical->value,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Faible',
            self::Medium => 'Moyen',
            self::High => 'Élevé',
            self::Critical => 'Critique',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'green',
            self::Medium => 'yellow',
            self::High => 'orange',
            self::Critical => 'red',
        };
    }

    public function isCritical(): bool
    {
        return $this === self::Critical;
    }
}
