<?php

namespace App\Domain\Risk\Enums;

enum RiskStatus: string
{
    case Identifie = 'identifie';
    case EnAnalyse = 'en_analyse';
    case EnMitigation = 'en_mitigation';
    case Mitige = 'mitige';
    case Accepte = 'accepte';
    case Ferme = 'ferme';

    public function label(): string
    {
        return match ($this) {
            self::Identifie => 'Identifié',
            self::EnAnalyse => 'En analyse',
            self::EnMitigation => 'En mitigation',
            self::Mitige => 'Mitigé',
            self::Accepte => 'Accepté',
            self::Ferme => 'Fermé',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
