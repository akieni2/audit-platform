<?php

namespace App\Domain\Risk\Enums;

enum RiskLifecycleStatus: string
{
    case Detected = 'detected';
    case Reviewed = 'reviewed';
    case Qualified = 'qualified';
    case Approved = 'approved';
    case Promoted = 'promoted';
    case Mitigated = 'mitigated';
    case Closed = 'closed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Detected => 'Détecté',
            self::Reviewed => 'Relu',
            self::Qualified => 'Qualifié',
            self::Approved => 'Approuvé',
            self::Promoted => 'Promu',
            self::Mitigated => 'Mitigé',
            self::Closed => 'Clos',
            self::Archived => 'Archivé',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
