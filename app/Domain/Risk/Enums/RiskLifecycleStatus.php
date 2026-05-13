<?php

namespace App\Domain\Risk\Enums;

enum RiskLifecycleStatus: string
{
    case Detected = 'detected';
    case UnderReview = 'under_review';
    case Validated = 'validated';
    case Promoted = 'promoted';
    case Mitigated = 'mitigated';
    case Closed = 'closed';
    case Archived = 'archived';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Detected => 'Détecté',
            self::UnderReview => 'En revue',
            self::Validated => 'Validé',
            self::Promoted => 'Promu',
            self::Mitigated => 'Mitigé',
            self::Closed => 'Clos',
            self::Archived => 'Archivé',
            self::Rejected => 'Rejeté',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Detected => 'slate',
            self::UnderReview => 'amber',
            self::Validated => 'emerald',
            self::Promoted => 'cyan',
            self::Mitigated => 'blue',
            self::Closed => 'green',
            self::Archived => 'zinc',
            self::Rejected => 'rose',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];

        foreach (self::cases() as $status) {
            $labels[$status->value] = $status->label();
        }

        return $labels;
    }

    /**
     * @return array<string, string>
     */
    public static function colors(): array
    {
        $colors = [];

        foreach (self::cases() as $status) {
            $colors[$status->value] = $status->color();
        }

        return $colors;
    }

    /**
     * @return list<string>
     */
    public static function terminalStates(): array
    {
        return [
            self::Closed->value,
            self::Archived->value,
            self::Rejected->value,
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromMixed(?string $value): self
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            '', self::Detected->value => self::Detected,
            'reviewed', 'qualified', self::UnderReview->value => self::UnderReview,
            'approved', self::Validated->value => self::Validated,
            self::Promoted->value => self::Promoted,
            self::Mitigated->value => self::Mitigated,
            self::Closed->value => self::Closed,
            self::Archived->value => self::Archived,
            self::Rejected->value => self::Rejected,
            default => self::tryFrom($normalized) ?? self::Detected,
        };
    }
}
