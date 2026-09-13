<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Mission;
use App\Models\Entretien;
use App\Models\IdentifiedRisk;
use App\Models\MissionTeamMember;
use App\Models\Risque;
use App\Models\Role;
use Database\Seeders\EndToEndPlatformValidationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndToEndPlatformValidationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_a_complete_and_idempotent_validation_scenario(): void
    {
        Department::query()->create(['name' => 'Direction générale', 'code' => 'DGCPT', 'type' => 'direction_generale', 'active' => true]);
        $dgcpt = Department::query()->where('code', 'DGCPT')->firstOrFail();
        Department::query()->create(['name' => 'Inspection des services', 'code' => 'IS', 'type' => 'inspection_services', 'parent_department_id' => $dgcpt->id, 'active' => true]);

        foreach (['inspecteur_adjoint', 'inspecteur_verificateur', 'inspecteur_verificateur_adjoint', 'directeur', 'chef_service'] as $index => $slug) {
            Role::query()->create(['slug' => $slug, 'name' => $slug, 'hierarchy_level' => 100 - $index, 'active' => true]);
        }

        $this->seed(EndToEndPlatformValidationSeeder::class);
        $this->seed(EndToEndPlatformValidationSeeder::class);

        $mission = Mission::query()->where('reference', EndToEndPlatformValidationSeeder::REFERENCE)->firstOrFail();
        $this->assertSame(4, MissionTeamMember::query()->where('mission_id', $mission->id)->count());
        $this->assertSame(2, $mission->auditGroups()->count());
        $this->assertSame(2, $mission->services()->count());
        $entretien = Entretien::query()->where('mission_id', $mission->id)->firstOrFail();
        $this->assertSame(6, $entretien->questionnaireResponses()->count());
        $this->assertGreaterThanOrEqual(1, IdentifiedRisk::query()->where('mission_id', $mission->id)->count());
        $this->assertGreaterThanOrEqual(1, Risque::query()->whereIn('source_identified_risk_id', IdentifiedRisk::query()->where('mission_id', $mission->id)->select('id'))->count());
        $this->assertSame(1, $mission->swotAnalyses()->count());
        $this->assertSame(1, $mission->raciMatrices()->count());
        $this->assertDatabaseCount('missions', 1);
    }
}
