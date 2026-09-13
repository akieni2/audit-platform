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
            self::Responsible => 'Responsable de l’exécution',
            self::Accountable => 'Autorité responsable',
            self::Consulted => 'Consulté',
            self::Informed => 'Informé',
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
