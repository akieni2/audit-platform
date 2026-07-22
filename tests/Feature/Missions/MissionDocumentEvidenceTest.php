<?php

namespace Tests\Feature\Missions;

use App\Models\Department;
use App\Models\Mission;
use App\Models\MissionAuditGroup;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireSection;
use App\Models\QuestionnaireTemplate;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MissionDocumentEvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_stores_and_downloads_a_document_linked_to_an_expected_question(): void
    {
        Storage::fake('local');
        $department = Department::query()->create([
            'name' => 'Pôle informatique', 'code' => 'PI', 'type' => 'pole', 'active' => true,
        ]);
        $supervisor = User::factory()->create([
            'department_id' => $department->id, 'approval_status' => 'approved', 'active' => true,
        ]);
        $department->update(['supervisor_user_id' => $supervisor->id]);
        $mission = Mission::query()->create([
            'organisation' => 'Audit DSI', 'date_debut' => today(), 'auditeur_id' => $supervisor->id,
            'department_id' => $department->id, 'mission_status' => Mission::STATUS_BROUILLON,
        ]);
        $service = Service::query()->create([
            'mission_id' => $mission->id, 'nom' => 'Service Réseau', 'active' => true,
        ]);
        $template = QuestionnaireTemplate::query()->create([
            'name' => 'Alignement stratégique', 'slug' => 'alignement-documents', 'active' => true,
            'lifecycle_status' => QuestionnaireTemplate::STATUS_PUBLISHED, 'is_global_template' => true,
        ]);
        $section = QuestionnaireSection::query()->create([
            'questionnaire_template_id' => $template->id, 'title' => 'Vision et stratégie',
            'section_type' => QuestionnaireSection::TYPE_SUBTHEME,
        ]);
        $question = QuestionnaireQuestion::query()->create([
            'questionnaire_section_id' => $section->id,
            'question' => 'Le SDSI est-il formalisé ?',
            'question_type' => QuestionnaireQuestion::TYPE_BOOLEAN_NA,
            'expected_documents' => 'Schéma directeur du système d’information',
            'active' => true,
        ]);
        $group = MissionAuditGroup::query()->create([
            'mission_id' => $mission->id, 'name' => 'Équipe A',
            'questionnaire_template_id' => $template->id, 'service_id' => $service->id,
            'created_by' => $supervisor->id,
        ]);

        $this->actingAs($supervisor)->post(route('missions.services.documents.store', [$mission, $service]), [
            'file' => UploadedFile::fake()->create('sdsi.pdf', 120, 'application/pdf'),
            'questionnaire_question_id' => $question->id,
            'mission_audit_group_id' => $group->id,
            'expected_document_label' => 'SDSI 2025–2027 signé',
            'receipt_status' => 'received',
            'category' => 'preuve',
        ])->assertRedirect();

        $this->assertDatabaseHas('mission_documents', [
            'mission_id' => $mission->id,
            'service_id' => $service->id,
            'questionnaire_question_id' => $question->id,
            'mission_audit_group_id' => $group->id,
            'expected_document_label' => 'SDSI 2025–2027 signé',
            'receipt_status' => 'received',
            'version' => 1,
        ]);

        $document = $mission->missionDocuments()->firstOrFail();
        $this->assertNotNull($document->checksum_sha256);
        Storage::disk('local')->assertExists($document->path);
        $this->actingAs($supervisor)
            ->get(route('mission-documents.download', $document))
            ->assertOk();
    }
}
