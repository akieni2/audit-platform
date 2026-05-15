<?php

namespace App\Domain\Raci\Enums;

enum RaciRoleType: string
{
    case Responsible = 'responsible';
    case Accountable = 'accountable';
    case Consulted = 'consulted';
    case Informed = 'informed';

    public function label(): string
    {
        return match ($this) {
            self::Responsible => 'Responsible',
            self::Accountable => 'Accountable',
            self::Consulted => 'Consulted',
            self::Informed => 'Informed',
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
