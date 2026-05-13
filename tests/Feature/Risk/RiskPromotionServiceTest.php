<?php

namespace Tests\Feature\Risk;

use App\Models\Department;
use App\Models\IdentifiedRisk;
use App\Models\Mission;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Services\Risk\RiskPromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskPromotionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_promotes_identified_risk_into_official_register_with_legacy_bridge(): void
    {
        $department = Department::query()->create([
            'name' => 'Direction Test',
            'code' => 'DIR-T',
            'type' => 'pole',
            'active' => true,
        ]);

        $role = Role::query()->create([
            'slug' => 'inspecteur_services',
            'name' => 'Inspecteur des Services',
            'hierarchy_level' => 100,
            'active' => true,
        ]);

        $user = User::factory()->create([
            'department_id' => $department->id,
            'role_id' => $role->id,
            'approval_status' => User::APPROVAL_STATUS_APPROVED,
            'active' => true,
        ]);

        $mission = Mission::query()->create([
            'organisation' => 'Org promotion',
            'description' => 'Test',
            'date_debut' => now()->toDateString(),
            'auditeur_id' => $user->id,
            'department_id' => $department->id,
            'mission_status' => Mission::STATUS_EN_COURS,
        ]);

        $service = Service::query()->create([
            'mission_id' => $mission->id,
            'nom' => 'Service Marches',
            'responsable' => 'Responsable S',
        ]);

        $identifiedRisk = IdentifiedRisk::query()->create([
            'mission_id' => $mission->id,
            'service_id' => $service->id,
            'title' => 'Absence de segregation',
            'description' => 'Une meme personne cumule validation et paiement.',
            'category' => 'Controle interne',
            'probability' => '4',
            'impact' => '5',
            'criticality' => 'critical',
            'created_by' => $user->id,
        ]);

        $risque = app(RiskPromotionService::class)->promote($identifiedRisk, $user, 'Promotion de test');

        $this->assertDatabaseHas('processus', [
            'mission_id' => $mission->id,
            'nom' => 'Core intake - Service Marches',
        ]);

        $this->assertDatabaseHas('actifs', [
            'processus_id' => $risque->actif_id,
            'nom' => 'Service - Service Marches',
        ]);

        $this->assertDatabaseHas('risques', [
            'id' => $risque->id,
            'identified_risk_id' => $identifiedRisk->id,
            'lifecycle_status' => 'promoted',
            'criticite_inherent' => 'critical',
        ]);

        $this->assertDatabaseHas('identified_risks', [
            'id' => $identifiedRisk->id,
            'validated_by_human' => 1,
            'lifecycle_status' => 'promoted',
        ]);

        $this->assertDatabaseHas('mission_risk_projections', [
            'mission_id' => $mission->id,
            'official_count' => 1,
        ]);
    }
}
