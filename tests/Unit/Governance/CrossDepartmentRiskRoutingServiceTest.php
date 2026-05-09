<?php

namespace Tests\Unit\Governance;

use App\Models\Department;
use App\Services\Governance\CrossDepartmentRiskRoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossDepartmentRiskRoutingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_it_department_from_si_keywords(): void
    {
        Department::query()->create([
            'name' => 'IT test',
            'code' => 'IT',
            'description' => null,
            'type' => 'pole',
            'active' => true,
        ]);

        $service = new CrossDepartmentRiskRoutingService;

        $dept = $service->detectTargetDepartment('Faille de sécurité sur le serveur et sauvegarde absente');

        $this->assertNotNull($dept);
        $this->assertSame('IT', $dept->code);
    }

    public function test_returns_null_when_no_match(): void
    {
        Department::query()->create([
            'name' => 'IT test',
            'code' => 'IT',
            'description' => null,
            'type' => 'pole',
            'active' => true,
        ]);

        $service = new CrossDepartmentRiskRoutingService;

        $this->assertNull($service->detectTargetDepartment('RAS'));
    }
}
