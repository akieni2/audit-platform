<?php

namespace App\Support;

final class OrganizationStructure
{
    public const DIRECTION_GENERALE = 'direction_generale';
    public const ADMINISTRATION = 'administration';
    public const DIRECTION = 'direction';
    public const DEPARTEMENT = 'departement';
    public const INSPECTION_SERVICES = 'inspection_services';
    public const SOUS_DIRECTION = 'sous_direction';
    public const POLE = 'pole';
    public const SERVICE = 'service';
    public const CELLULE = 'cellule';
    public const CABINET = 'cabinet';

    /** @return array<string, string> */
    public static function typeOptions(): array
    {
        return [
            self::DIRECTION_GENERALE => 'Direction générale',
            self::ADMINISTRATION => 'Administration',
            self::DIRECTION => 'Direction',
            self::DEPARTEMENT => 'Département',
            self::INSPECTION_SERVICES => 'Inspection des Services',
            self::SOUS_DIRECTION => 'Sous-direction',
            self::POLE => 'Pôle',
            self::SERVICE => 'Service',
            self::CELLULE => 'Cellule',
            self::CABINET => 'Cabinet',
        ];
    }

    public static function label(?string $type): string
    {
        return self::typeOptions()[$type ?? ''] ?? 'Structure';
    }

    public static function defaultHeadTitle(?string $type): string
    {
        return match ($type) {
            self::DIRECTION_GENERALE => 'Directeur général',
            self::INSPECTION_SERVICES => 'Inspecteur des Services',
            self::ADMINISTRATION, self::DIRECTION => 'Directeur',
            self::DEPARTEMENT => 'Chef de département',
            self::SOUS_DIRECTION, self::POLE => 'Responsable de structure',
            self::SERVICE => 'Chef de service',
            self::CELLULE => 'Chef de cellule',
            self::CABINET => 'Directeur de cabinet',
            default => 'Responsable hiérarchique',
        };
    }

    /** @return array<string, string> */
    public static function positionOptions(): array
    {
        return [
            'Directeur général' => 'Directeur général',
            'Inspecteur des Services' => 'Inspecteur des Services',
            'Inspecteur des Services adjoint' => 'Inspecteur des Services adjoint',
            'Directeur' => 'Directeur',
            'Directeur adjoint' => 'Directeur adjoint',
            'Chef de département' => 'Chef de département',
            'Chef de service' => 'Chef de service',
            'Chef de cellule' => 'Chef de cellule',
            'Responsable de pôle' => 'Responsable de pôle',
            'Inspecteur vérificateur' => 'Inspecteur vérificateur',
            'Inspecteur vérificateur adjoint' => 'Inspecteur vérificateur adjoint',
            'Agent opérationnel' => 'Agent opérationnel',
        ];
    }

    public static function recommendedRoleSlug(?string $type, ?string $parentType = null): ?string
    {
        return match ($type) {
            self::INSPECTION_SERVICES => UserRoles::INSPECTEUR_SERVICES,
            self::SOUS_DIRECTION, self::POLE => $parentType === self::INSPECTION_SERVICES
                ? UserRoles::INSPECTEUR_ADJOINT
                : UserRoles::DIRECTEUR_ADJOINT,
            self::ADMINISTRATION, self::DIRECTION, self::DEPARTEMENT => UserRoles::DIRECTEUR,
            self::SERVICE, self::CELLULE => UserRoles::CHEF_SERVICE,
            default => null,
        };
    }

    public static function requiresParent(?string $type): bool
    {
        return in_array($type, [self::SOUS_DIRECTION, self::POLE, self::SERVICE, self::CELLULE], true);
    }

    public static function requiresAuditMethodology(?string $type): bool
    {
        return in_array($type, [
            self::ADMINISTRATION,
            self::DIRECTION,
            self::DEPARTEMENT,
            self::INSPECTION_SERVICES,
            self::SOUS_DIRECTION,
            self::POLE,
        ], true);
    }

    /** @return list<string> */
    public static function allowedParentTypes(?string $type): array
    {
        return match ($type) {
            self::SOUS_DIRECTION, self::POLE => [self::ADMINISTRATION, self::DIRECTION, self::DEPARTEMENT, self::INSPECTION_SERVICES],
            self::SERVICE => [self::ADMINISTRATION, self::DIRECTION, self::DEPARTEMENT, self::INSPECTION_SERVICES, self::SOUS_DIRECTION, self::POLE],
            self::CELLULE => [self::DIRECTION_GENERALE, self::ADMINISTRATION, self::DIRECTION, self::DEPARTEMENT, self::INSPECTION_SERVICES, self::SOUS_DIRECTION, self::POLE],
            self::ADMINISTRATION, self::DIRECTION, self::DEPARTEMENT, self::INSPECTION_SERVICES, self::CABINET => [self::DIRECTION_GENERALE, self::ADMINISTRATION],
            default => [],
        };
    }
}
