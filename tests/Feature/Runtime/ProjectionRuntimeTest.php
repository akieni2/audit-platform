<?php

namespace Tests\Feature\Runtime;

use App\Domain\Questionnaires\Events\EntretienResponsesRecorded;
use App\Jobs\RefreshMissionRiskProjectionJob;
use App\Models\Actif;
use App\Models\Department;
use App\Models\Entretien;
use App\Models\Mission;
use App\Models\Processus;
use App\Models\Risque;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Services\Risk\MissionRiskProjectionService;
use App\Services\Risk\ProjectionIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProjectionRuntimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_projection_refresh_is_idempotent_when_source_signature_does_not_change(): void
    {
        [$mission] = $this->missionWithOfficialRisk();

        $service = app(MissionRiskProjectionService::class);

        $first = $service->refreshForMissionId($mission->id);
        $second = $service->refreshForMissionId($mission->id);

        $this->assertSame($first->source_signature, $second->source_signature);
        $this->assertSame(1, $second->refresh_count);
    }

    public function test_projection_integrity_detects_mismatch_and_can_be_repaired(): void
    {
        [$mission] = $this->missionWithOfficialRisk();

        $projections = app(MissionRiskProjectionService::class);
        $projection = $projections->refreshForMissionId($mission->id);

        $projection->update([
            'official_count' => 999,
            'source_signature' => 'broken-signature',
        ]);

        $snapshot = $projections->computeSnapshot($mission->id);
        $check = app(ProjectionIntegrityService::class)->verifyMissionRiskProjection(
            missionId: $mission->id,
            expectedSnapshot: $snapshot,
            existingProjection: $projection->fresh(),
            repaired: false,
            correlationId: 'test-correlation',
        );

        $this->assertSame('mismatch', $check->status);
        $this->assertGreaterThan(0, $check->mismatch_count);

        $repaired = $projections->refreshForMissionId($mission->id, true, 'repair-correlation');

        $this->assertSame($snapshot['signature'], $repaired->source_signature);
        $this->assertSame($snapshot['counts']['official_count'], $repaired->official_count);
    }

    public function test_entretien_runtime_event_queues_projection_refresh_job(): void
    {
        Queue::fake();
        [$mission, $entretien] = $this->missionWithOfficialRisk(includeEntretien: true);

        event(new EntretienResponsesRecorded($entretien, [1], [], 'queue-correlation'));

        Queue::assertPushed(RefreshMissionRiskProjectionJob::class, function (RefreshMissionRiskProjectionJob $job) use ($mission): bool {
            return $job->missionId === $mission->id
                && $job->correlationId === 'queue-correlation'
                && $job->uniqueId() === 'mission-risk-projection:'.$mission->id;
        });
    }

    /**
     * @return array{Mission, Entretien|null}
     */
    private function missionWithOfficialRisk(bool $includeEntretien = false): array
    {
        $department = Department::query()->create([
            'name' => 'Pôle Projection',
            'code' => 'PRJ',
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
            'organisation' => 'Org projection',
            'description' => 'Projection test',
            'date_debut' => now()->toDateString(),
            'auditeur_id' => $user->id,
            'department_id' => $department->id,
            'mission_status' => Mission::STATUS_EN_COURS,
        ]);

        $service = Service::query()->create([
            'mission_id' => $mission->id,
            'nom' => 'Service Projection',
        ]);

        $processus = Processus::query()->create([
            'mission_id' => $mission->id,
            'nom' => 'Processus Projection',
        ]);

        $actif = Actif::query()->create([
            'processus_id' => $processus->id,
            'nom' => 'Actif Projection',
            'type' => 'support',
        ]);

        Risque::query()->create([
            'actif_id' => $actif->id,
            'description' => 'Risque projection',
            'impact_inherent' => 5,
            'probabilite_inherent' => 4,
            'statut_risque' => 'identifie',
            'lifecycle_status' => 'promoted',
        ])->calculerRisqueResiduel();

        $entretien = null;
        if ($includeEntretien) {
            $entretien = Entretien::query()->create([
                'mission_id' => $mission->id,
                'service_id' => $service->id,
                'status' => Entretien::STATUS_IN_PROGRESS,
            ]);
        }

        return [$mission, $entretien];
    }
}
