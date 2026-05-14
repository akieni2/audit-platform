<?php

namespace App\Domain\Workflow\Enums;

enum WorkflowVisualState: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Completed = 'completed';
    case Blocked = 'blocked';
    case Failed = 'failed';
    case Skipped = 'skipped';
    case AwaitingApproval = 'awaiting_approval';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Active => 'Active',
            self::Completed => 'Complétée',
            self::Blocked => 'Bloquée',
            self::Failed => 'En échec',
            self::Skipped => 'Ignorée',
            self::AwaitingApproval => 'En attente d’approbation',
            self::Archived => 'Archivée',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-[#17223B] text-[#BFD2E6] border-[rgba(191,210,230,0.18)]',
            self::Active => 'bg-[rgba(0,209,255,0.12)] text-[#73D8FF] border-[rgba(0,209,255,0.22)]',
            self::Completed => 'bg-[rgba(0,168,107,0.12)] text-[#7EF2BE] border-[rgba(0,168,107,0.24)]',
            self::Blocked => 'bg-[rgba(245,158,11,0.12)] text-[#FFD479] border-[rgba(245,158,11,0.24)]',
            self::Failed => 'bg-[rgba(255,90,90,0.12)] text-[#FFB4B4] border-[rgba(255,90,90,0.24)]',
            self::Skipped => 'bg-[rgba(125,211,252,0.12)] text-[#BAE6FD] border-[rgba(125,211,252,0.18)]',
            self::AwaitingApproval => 'bg-[rgba(201,174,255,0.14)] text-[#D8B4FE] border-[rgba(201,174,255,0.24)]',
            self::Archived => 'bg-[rgba(148,163,184,0.10)] text-[#CBD5E1] border-[rgba(148,163,184,0.18)]',
        };
    }

    public function cardClasses(): string
    {
        return match ($this) {
            self::Pending => 'border-[rgba(191,210,230,0.14)] bg-[rgba(9,15,28,0.72)]',
            self::Active => 'border-[rgba(0,209,255,0.22)] bg-[rgba(8,24,46,0.72)]',
            self::Completed => 'border-[rgba(0,168,107,0.22)] bg-[rgba(6,28,22,0.72)]',
            self::Blocked => 'border-[rgba(245,158,11,0.22)] bg-[rgba(33,24,8,0.72)]',
            self::Failed => 'border-[rgba(255,90,90,0.22)] bg-[rgba(36,10,15,0.72)]',
            self::Skipped => 'border-[rgba(125,211,252,0.18)] bg-[rgba(11,20,35,0.72)]',
            self::AwaitingApproval => 'border-[rgba(201,174,255,0.22)] bg-[rgba(28,15,39,0.72)]',
            self::Archived => 'border-[rgba(148,163,184,0.14)] bg-[rgba(12,18,26,0.72)]',
        };
    }

    public function accentColor(): string
    {
        return match ($this) {
            self::Pending => '#94A3B8',
            self::Active => '#00D1FF',
            self::Completed => '#00A86B',
            self::Blocked => '#F59E0B',
            self::Failed => '#FF5A5A',
            self::Skipped => '#7DD3FC',
            self::AwaitingApproval => '#C9AEFF',
            self::Archived => '#64748B',
        };
    }
}
