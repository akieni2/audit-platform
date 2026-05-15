<?php

namespace App\Domain\Swot\Enums;

enum SwotCategoryType: string
{
    case Strength = 'strength';
    case Weakness = 'weakness';
    case Opportunity = 'opportunity';
    case Threat = 'threat';

    public function label(): string
    {
        return match ($this) {
            self::Strength => 'Forces',
            self::Weakness => 'Faiblesses',
            self::Opportunity => 'Opportunites',
            self::Threat => 'Menaces',
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
