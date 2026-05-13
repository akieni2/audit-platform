<?php

namespace Tests\Feature\Concerns;

use App\Models\Actif;
use App\Models\Department;
use App\Models\IdentifiedRisk;
use App\Models\Mission;
use App\Models\Processus;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;

trait BuildsRiskRegistryContext
{
    /**
     * @param  array<string, mixed>  $riskOverrides
     * @return array{department: Department, user: User, mission: Mission, service: Service, identifiedRisk: IdentifiedRisk}
     */
    protected function createRiskRegistryContext(array $riskOverrides = []): array
    {
        $department = Department::query()->firstOrCreate(
            ['code' => 'DGOV'],
            [
                'name' => 'Direction Gouvernance',
                'type' => 'pole',
                'active' => true,
            ]
        );

        $role = Role::query()->firstOrCreate(
            ['slug' => 'inspecteur_services'],
            [
                'name' => 'Inspecteur des Services',
                'hierarchy_level' => 100,
                'active' => true,
            ]
        );

        $user = User::factory()->create([
            'department_id' => $department->id,
            'role_id' => $role->id,
            'approval_status' => User::APPROVAL_STATUS_APPROVED,
            'active' => true,
        ]);

        $mission = Mission::query()->create([
            'organisation' => 'Mission registre enterprise',
            'description' => 'Test',
            'date_debut' => now()->toDateString(),
            'auditeur_id' => $user->id,
            'department_id' => $department->id,
            'mission_status' => Mission::STATUS_EN_COURS,
        ]);

        $service = Service::query()->create([
            'mission_id' => $mission->id,
            'nom' => 'Service Marchés Publics',
            'responsable' => 'Chef service MP',
        ]);

        $identifiedRisk = IdentifiedRisk::query()->create(array_merge([
            'mission_id' => $mission->id,
            'service_id' => $service->id,
            'title' => 'Absence de séparation des tâches',
            'description' => 'Une même personne valide et paie.',
            'category' => 'Contrôle interne',
            'probability' => '4',
            'impact' => '5',
            'criticality' => 'critical',
            'lifecycle_status' => 'detected',
            'created_by' => $user->id,
        ], $riskOverrides));

        return compact('department', 'user', 'mission', 'service', 'identifiedRisk');
    }

    /**
     * @return array{actif: Actif, processus: Processus}
     */
    protected function createLegacyActifChain(Mission $mission): array
    {
        $processus = Processus::query()->create([
            'mission_id' => $mission->id,
            'nom' => 'Proc Legacy',
            'description' => 'Legacy',
        ]);

        $actif = Actif::query()->create([
            'processus_id' => $processus->id,
            'nom' => 'Actif Legacy',
            'type' => 'support',
            'description' => 'Legacy bridge',
        ]);

        return compact('actif', 'processus');
    }
}
