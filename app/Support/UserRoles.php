<?php

namespace App\Support;

/**
 * Rôles applicatifs (colonne users.role).
 */
final class UserRoles
{
    public const AUDITEUR = 'auditeur';

    public const MANAGER = 'manager';

    public const ADMIN = 'admin';

    public const RISK_MANAGER = 'risk_manager';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::AUDITEUR,
            self::MANAGER,
            self::RISK_MANAGER,
            self::ADMIN,
        ];
    }

    public static function label(string $role): string
    {
        return match ($role) {
            self::AUDITEUR => 'Auditeur',
            self::MANAGER => 'Manager',
            self::RISK_MANAGER => 'Risk Manager',
            self::ADMIN => 'Administrateur',
            default => $role,
        };
    }
}
