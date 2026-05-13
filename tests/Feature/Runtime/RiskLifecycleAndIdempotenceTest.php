<?php

namespace Tests\Feature\Runtime;

use App\Domain\Risk\Enums\RiskLifecycleStatus;
use App\Models\Department;
use App\Models\IdentifiedRisk;
use App\Models\Mission;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Services\Risk\RiskPromotionService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskLifecycleAndIdempotenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_promotion_attempts_return_same_official_risk(): void
    {
        [$user, $identifiedRisk] = $this->identifiedRiskContext();

        $service = app(RiskPromotionService::class);

        $first = $service->promote($identifiedRisk, $user, 'first');
        $second = $service->promote($identifiedRisk->fresh(), $user, 'second');

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('risques', 1);
        $this->assertDatabaseHas('identified_risks', [
            'id' => $identifiedRisk->id,
            'lifecycle_status' => RiskLifecycleStatus::Promoted->value,
        ]);
    }

    public function test_archived_identified_risk_cannot_be_promoted(): void
    {
        [$user, $identifiedRisk] = $this->identifiedRiskContext([
            'lifecycle_status' => RiskLifecycleStatus::Archived->value,
        ]);

        $this->expectException(DomainException::class);

        app(RiskPromotionService::class)->promote($identifiedRisk, $user, 'forbidden');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{User, IdentifiedRisk}
     */
    private function identifiedRiskContext(array $overrides = []): array
    {
        $department = Department::query()->create([
            'name' => 'Pôle Risque',
            'code' => 'RSK',
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
            'organisation' => 'Org lifecycle',
            'description' => 'Lifecycle test',
            'date_debut' => now()->toDateString(),
            'auditeur_id' => $user->id,
            'department_id' => $department->id,
            'mission_status' => Mission::STATUS_EN_COURS,
        ]);

        $service = Service::query()->create([
            'mission_id' => $mission->id,
            'nom' => 'Service Lifecycle',
        ]);

        $identifiedRisk = IdentifiedRisk::query()->create(array_merge([
            'mission_id' => $mission->id,
            'service_id' => $service->id,
            'title' => 'Absence de validation',
            'description' => 'Test lifecycle',
            'category' => 'Controle',
            'probability' => '4',
            'impact' => '4',
            'criticality' => 'eleve',
            'validated_by_human' => false,
            'lifecycle_status' => RiskLifecycleStatus::Detected->value,
            'created_by' => $user->id,
        ], $overrides));

        return [$user, $identifiedRisk];
    }
}
