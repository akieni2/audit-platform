<?php

namespace App\Domain\Dgcpt\Enums;

enum TreasuryEntityType: string
{
    case National = 'national';
    case Provincial = 'provincial';
    case Departmental = 'departmental';
    case International = 'international';

    public function label(): string
    {
        return match ($this) {
            self::National => 'National (DGCPT)',
            self::Provincial => 'Trésorerie provinciale',
            self::Departmental => 'Trésorerie départementale',
            self::International => 'Trésorerie internationale',
        };
    }

    public static function fromMixed(?string $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::tryFrom($value);
    }
}
