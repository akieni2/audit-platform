<?php

namespace App\Domain\Workflow\Enums;

enum WorkflowTemplateStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Deprecated = 'deprecated';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Published => 'Publié',
            self::Deprecated => 'Déprécié',
            self::Archived => 'Archivé',
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
}
