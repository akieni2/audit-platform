<?php

namespace Tests\Feature;

use App\Models\Dgcpt\AuditDomain;
use App\Models\Dgcpt\TreasuryEntity;
use Database\Seeders\DgcptTreasuryFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsWorkflowRuntimeContext;
use Tests\TestCase;

class DgcptHierarchyTest extends TestCase
{
    use BuildsWorkflowRuntimeContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DgcptTreasuryFoundationSeeder::class);
    }

    public function test_hierarchy_page_lists_national_and_provincial_entities(): void
    {
        $department = $this->createDepartment('DGC');
        $user = $this->createUser('inspecteur_services', $department);

        $this->actingAs($user)
            ->get(route('dgcpt.hierarchy.index'))
            ->assertOk()
            ->assertSee('Hiérarchie Trésor public')
            ->assertSee('DGCPT')
            ->assertSee('Trésorerie Provinciale de l\'Ogooué-Maritime');
    }

    public function test_national_consolidation_page_renders(): void
    {
        $department = $this->createDepartment('DGN');
        $user = $this->createUser('inspecteur_services', $department);

        $this->actingAs($user)
            ->get(route('dgcpt.consolidation.national'))
            ->assertOk()
            ->assertSee('Vue nationale DGCPT');
    }

    public function test_questionnaire_import_detects_tp_lambarene_context(): void
    {
        $service = app(\App\Services\Dgcpt\QuestionnaireImportService::class);
        $detection = $service->detectContextFromFilename('TP_Lambarene.docx');

        $this->assertSame('TP-MO', $detection['suggested_entity']['code'] ?? null);
        $this->assertSame('AUDIT_SI', $detection['suggested_domain']['code'] ?? null);
        $this->assertSame('TPL_AUDIT_SI_TP', $detection['suggested_template']['code'] ?? null);
    }

    public function test_mission_can_link_treasury_context(): void
    {
        $department = $this->createDepartment('DGM');
        $user = $this->createUser('inspecteur_services', $department);
        $mission = $this->createMission($user, $department);

        $entity = TreasuryEntity::query()->where('code', 'TP-OM')->first();
        $domain = AuditDomain::query()->where('code', 'AUDIT_SI')->first();

        $mission->update([
            'treasury_entity_id' => $entity?->id,
            'audit_domain_id' => $domain?->id,
        ]);

        $mission->refresh();
        $this->assertSame($entity?->id, $mission->treasury_entity_id);
        $this->assertSame('TP-OM', $mission->treasuryEntity?->code);
    }
}
