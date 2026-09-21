<?php

namespace Tests\Feature\Missions;

use App\Models\Department;
use App\Models\Mission;
use App\Models\MissionAuditGroup;
use App\Models\MissionDocumentRequest;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireSection;
use App\Models\QuestionnaireTemplate;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MissionDocumentRequestTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_expected_documents_generate_reusable_mission_requests_without_duplicates(): void
    {
        [$supervisor, $mission, $service, $question] = $this->missionContext();

        $route = route('missions.services.document-requests.generate', [$mission, $service]);
        $this->actingAs($supervisor)->post($route)->assertRedirect();
        $this->actingAs($supervisor)->post($route)->assertRedirect();

        $this->assertDatabaseCount('mission_document_requests', 3);
        $this->assertDatabaseHas('mission_document_requests', [
            'mission_id' => $mission->id,
            'service_id' => $service->id,
            'questionnaire_question_id' => $question->id,
            'label' => 'Schéma directeur du système d’information',
            'status' => MissionDocumentRequest::STATUS_DRAFT,
        ]);
        $this->assertDatabaseHas('mission_document_requests', ['label' => 'Note de validation']);
        $this->assertDatabaseHas('mission_document_requests', ['label' => 'Plan d’actions associé']);
        $this->actingAs($supervisor)
            ->get(route('missions.services.documents.index', [$mission, $service]))
            ->assertOk()
            ->assertSee('Suivi des demandes documentaires')
            ->assertSee('Schéma directeur du système d’information');
    }

    public function test_received_file_is_linked_to_request_and_updates_its_status(): void
    {
        Storage::fake('local');
        [$supervisor, $mission, $service] = $this->missionContext();
        $this->actingAs($supervisor)
            ->post(route('missions.services.document-requests.generate', [$mission, $service]));
        $documentRequest = MissionDocumentRequest::query()->firstOrFail();

        $this->actingAs($supervisor)->post(route('missions.services.documents.store', [$mission, $service]), [
            'file' => UploadedFile::fake()->create('preuve.pdf', 80, 'application/pdf'),
            'mission_document_request_id' => $documentRequest->id,
            'receipt_status' => 'to_review',
            'category' => 'preuve',
        ])->assertRedirect();

        $this->assertDatabaseHas('mission_documents', [
            'mission_document_request_id' => $documentRequest->id,
            'questionnaire_question_id' => $documentRequest->questionnaire_question_id,
            'expected_document_label' => $documentRequest->label,
        ]);
        $this->assertDatabaseHas('mission_document_requests', [
            'id' => $documentRequest->id,
            'status' => MissionDocumentRequest::STATUS_TO_REVIEW,
        ]);
    }

    public function test_mission_contributor_can_update_request_review_status_and_due_date(): void
    {
        [$supervisor, $mission, $service] = $this->missionContext();
        $this->actingAs($supervisor)
            ->post(route('missions.services.document-requests.generate', [$mission, $service]));
        $documentRequest = MissionDocumentRequest::query()->firstOrFail();

        $this->actingAs($supervisor)
            ->patch(route('mission-document-requests.update', $documentRequest), [
                'status' => MissionDocumentRequest::STATUS_ACCEPTED,
                'due_at' => '2026-10-15',
                'review_notes' => 'Preuve suffisante et validée.',
            ])->assertRedirect();

        $this->assertDatabaseHas('mission_document_requests', [
            'id' => $documentRequest->id,
            'status' => MissionDocumentRequest::STATUS_ACCEPTED,
            'due_at' => '2026-10-15 00:00:00',
            'review_notes' => 'Preuve suffisante et validée.',
        ]);
        $this->assertNotNull($documentRequest->fresh()->closed_at);
    }

    /** @return array{User, Mission, Service, QuestionnaireQuestion} */
    private function missionContext(): array
    {
        $department = Department::query()->create([
            'name' => 'Pôle audit test', 'code' => 'PAT-DOC', 'type' => 'pole', 'active' => true,
        ]);
        $supervisor = User::factory()->create([
            'department_id' => $department->id, 'approval_status' => 'approved', 'active' => true,
        ]);
        $department->update(['supervisor_user_id' => $supervisor->id]);
        $mission = Mission::query()->create([
            'organisation' => 'Trésorerie test', 'reference' => 'AUD-DOC-001',
            'date_debut' => today(), 'auditeur_id' => $supervisor->id,
            'department_id' => $department->id, 'mission_status' => Mission::STATUS_BROUILLON,
        ]);
        $service = Service::query()->create([
            'mission_id' => $mission->id, 'nom' => 'Service audité', 'active' => true,
        ]);
        $template = QuestionnaireTemplate::query()->create([
            'name' => 'Gouvernance SI', 'slug' => 'gouvernance-si-documentaire', 'active' => true,
            'lifecycle_status' => QuestionnaireTemplate::STATUS_PUBLISHED, 'is_global_template' => true,
        ]);
        $section = QuestionnaireSection::query()->create([
            'questionnaire_template_id' => $template->id, 'title' => 'Schéma directeur',
            'section_type' => QuestionnaireSection::TYPE_SUBTHEME,
        ]);
        $question = QuestionnaireQuestion::query()->create([
            'questionnaire_section_id' => $section->id,
            'question' => 'Le schéma directeur est-il validé et suivi ?',
            'question_type' => QuestionnaireQuestion::TYPE_BOOLEAN_NA,
            'expected_documents' => "Schéma directeur du système d’information; Note de validation\n- Plan d’actions associé",
            'required' => true,
            'active' => true,
        ]);
        MissionAuditGroup::query()->create([
            'mission_id' => $mission->id, 'name' => 'Équipe documentaire',
            'questionnaire_template_id' => $template->id, 'service_id' => $service->id,
            'created_by' => $supervisor->id,
        ]);

        return [$supervisor, $mission, $service, $question];
    }
}
