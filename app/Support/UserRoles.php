<?php

namespace App\Support;

/**
 * Valeurs autorisées pour la colonne legacy users.role (formulaires admin).
 * Les rôles institutionnels sont aussi dans la table roles (role_id).
 */
final class UserRoles
{
    public const AUDITEUR = 'auditeur';

    public const MANAGER = 'manager';

    public const ADMIN = 'admin';

    public const RISK_MANAGER = 'risk_manager';

    public const INSPECTEUR_SERVICES = 'inspecteur_services';

    public const INSPECTEUR_ADJOINT = 'inspecteur_adjoint';

    public const INSPECTEUR_VERIFICATEUR = 'inspecteur_verificateur';

    public const INSPECTEUR_VERIFICATEUR_ADJOINT = 'inspecteur_verificateur_adjoint';

    public const CHARGE_VERIFICATION = 'charge_verification';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::INSPECTEUR_SERVICES,
            self::INSPECTEUR_ADJOINT,
            self::INSPECTEUR_VERIFICATEUR,
            self::INSPECTEUR_VERIFICATEUR_ADJOINT,
            self::CHARGE_VERIFICATION,
            self::ADMIN,
            self::RISK_MANAGER,
            self::MANAGER,
            self::AUDITEUR,
        ];
    }

    public static function label(string $role): string
    {
        return match ($role) {
            self::AUDITEUR => 'Auditeur',
            self::MANAGER => 'Manager',
            self::RISK_MANAGER => 'Risk Manager',
            self::ADMIN => 'Administrateur technique',
            self::INSPECTEUR_SERVICES => 'Inspecteur des Services',
            self::INSPECTEUR_ADJOINT => 'Inspecteur adjoint',
            self::INSPECTEUR_VERIFICATEUR => 'Inspecteur vérificateur',
            self::INSPECTEUR_VERIFICATEUR_ADJOINT => 'Inspecteur vérificateur adjoint',
            self::CHARGE_VERIFICATION => 'Chargé de vérification',
            default => $role,
        };
    }
}
