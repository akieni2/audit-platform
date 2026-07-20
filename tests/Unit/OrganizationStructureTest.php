<?php

namespace Tests\Unit;

use App\Support\OrganizationStructure;
use App\Support\UserRoles;
use PHPUnit\Framework\TestCase;

class OrganizationStructureTest extends TestCase
{
    public function test_inspection_poles_are_led_by_inspecteurs_adjoints(): void
    {
        $this->assertSame(
            UserRoles::INSPECTEUR_ADJOINT,
            OrganizationStructure::recommendedRoleSlug(
                OrganizationStructure::POLE,
                OrganizationStructure::INSPECTION_SERVICES
            )
        );
    }

    public function test_direction_services_are_led_by_chefs_de_service(): void
    {
        $this->assertSame(
            UserRoles::CHEF_SERVICE,
            OrganizationStructure::recommendedRoleSlug(
                OrganizationStructure::SERVICE,
                OrganizationStructure::DIRECTION
            )
        );
    }

    public function test_operational_levels_require_a_parent(): void
    {
        $this->assertTrue(OrganizationStructure::requiresParent(OrganizationStructure::POLE));
        $this->assertTrue(OrganizationStructure::requiresParent(OrganizationStructure::SERVICE));
        $this->assertFalse(OrganizationStructure::requiresParent(OrganizationStructure::DIRECTION));
    }

    public function test_legacy_department_type_remains_available(): void
    {
        $this->assertSame(
            'Département',
            OrganizationStructure::typeOptions()[OrganizationStructure::DEPARTEMENT]
        );
    }

    public function test_audit_bearing_structures_require_a_methodology(): void
    {
        $this->assertTrue(OrganizationStructure::requiresAuditMethodology(OrganizationStructure::DIRECTION));
        $this->assertTrue(OrganizationStructure::requiresAuditMethodology(OrganizationStructure::POLE));
        $this->assertFalse(OrganizationStructure::requiresAuditMethodology(OrganizationStructure::SERVICE));
    }
}
