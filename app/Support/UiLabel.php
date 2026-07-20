<?php

namespace App\Support;

use App\Domain\Risk\Enums\CriticalityLevel;
use BackedEnum;

final class UiLabel
{
    public static function translate(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        $raw = trim((string) $value);

        if ($raw === '') {
            return '—';
        }

        if ($criticality = CriticalityLevel::fromMixed($raw)) {
            return $criticality->label();
        }

        return self::labels()[mb_strtolower($raw)] ?? $raw;
    }

    /** @return array<string, string> */
    private static function labels(): array
    {
        return [
            'accountable' => 'Responsable final',
            'active' => 'Actif',
            'approved' => 'Approuvé',
            'archived' => 'Archivé',
            'blocked' => 'Bloqué',
            'closed' => 'Clôturé',
            'completed' => 'Terminé',
            'draft' => 'Brouillon',
            'failed' => 'Échec',
            'inactive' => 'Inactif',
            'in_progress' => 'En cours',
            'open' => 'Ouvert',
            'pending' => 'En attente',
            'published' => 'Publié',
            'rejected' => 'Rejeté',
            'running' => 'En cours',
            'skipped' => 'Ignoré',
            'validated' => 'Validé',
        ];
    }
}
