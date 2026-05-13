<?php

namespace Tests\Feature\Questionnaires;

use App\Models\Department;
use App\Models\Entretien;
use App\Models\Mission;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireSection;
use App\Models\QuestionnaireTemplate;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionnaireDynamicFlowTest extends TestCase
{
    use RefreshDatabase;

    private function inspecteurNational(): User
    {
        $role = Role::query()->create([
            'slug' => 'inspecteur_services',
            'name' => 'Inspecteur des Services',
            'hierarchy_level' => 100,
            'active' => true,
        ]);

        return User::factory()->create([
            'department_id' => null,
            'role_id' => $role->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);
    }

    private function department(): Department
    {
        return Department::query()->create([
            'name' => 'Pôle test',
            'code' => 'POLE-T',
            'type' => 'pole',
            'description' => 'Test',
            'active' => true,
        ]);
    }

    public function test_national_inspector_can_create_template_section_question_and_save_entretien_response(): void
    {
        $user = $this->inspecteurNational();
        $dept = $this->department();

        $mission = Mission::query()->create([
            'organisation' => 'Org test questionnaire',
            'description' => 'd',
            'date_debut' => Carbon::today(),
            'date_fin' => null,
            'auditeur_id' => $user->id,
            'department_id' => $dept->id,
            'mission_status' => Mission::STATUS_BROUILLON,
        ]);

        $service = Service::query()->create([
            'mission_id' => $mission->id,
            'nom' => 'Service RH',
            'description' => null,
        ]);

        $this->actingAs($user);

        $tplResp = $this->post(route('questionnaire-templates.store'), [
            'name' => 'Modèle test dynamique',
            'description' => 'Test',
            'mission_type' => 'audit',
            'active' => '1',
        ]);
        $tplResp->assertRedirect();
        $template = QuestionnaireTemplate::query()->where('name', 'Modèle test dynamique')->firstOrFail();

        $secResp = $this->post(route('questionnaire-templates.sections.store', $template), [
            'title' => 'Organisation',
            'description' => null,
            'sort_order' => 0,
        ]);
        $secResp->assertRedirect();
        $section = QuestionnaireSection::query()->where('questionnaire_template_id', $template->id)->firstOrFail();

        $qResp = $this->post(route('questionnaire-templates.questions.store', [$template, $section]), [
            'code' => 'Q1',
            'question' => 'Disposez-vous de procédures écrites ?',
            'help_text' => null,
            'question_type' => QuestionnaireQuestion::TYPE_BOOLEAN_NA,
            'required' => '1',
            'allows_observation' => '1',
            'allows_risk_detection' => '0',
            'sort_order' => 0,
        ]);
        $qResp->assertRedirect();
        $question = QuestionnaireQuestion::query()->where('questionnaire_section_id', $section->id)->firstOrFail();

        $entretien = Entretien::query()->create([
            'mission_id' => $mission->id,
            'service_id' => $service->id,
            'questionnaire_template_id' => $template->id,
            'responsable_nom' => 'Dupont',
            'role' => 'Responsable',
            'chef_hierarchique' => null,
            'auditeur' => null,
            'date_entretien' => Carbon::today()->format('Y-m-d'),
            'notes' => null,
        ]);

        $this->get(route('entretiens.conduite.show', $entretien))->assertOk();

        $this->assertDatabaseHas('entretiens', [
            'id' => $entretien->id,
            'questionnaire_snapshot_version' => 1,
        ]);

        $save = $this->post(route('entretiens.dynamic-responses.store', $entretien), [
            'responses' => [
                [
                    'questionnaire_question_id' => $question->id,
                    'answer_tri' => 'no',
                    'observation' => 'Transmission orale uniquement.',
                ],
            ],
        ]);
        $save->assertRedirect(route('entretiens.conduite.show', $entretien));

        $this->assertDatabaseHas('entretien_responses', [
            'entretien_id' => $entretien->id,
            'questionnaire_question_id' => $question->id,
            'answer_boolean' => 0,
        ]);
    }

    public function test_identified_risk_created_when_submitted_with_allows_risk_detection(): void
    {
        $user = $this->inspecteurNational();
        $dept = $this->department();

        $mission = Mission::query()->create([
            'organisation' => 'Org risque',
            'description' => 'd',
            'date_debut' => Carbon::today(),
            'date_fin' => null,
            'auditeur_id' => $user->id,
            'department_id' => $dept->id,
            'mission_status' => Mission::STATUS_BROUILLON,
        ]);

        $service = Service::query()->create([
            'mission_id' => $mission->id,
            'nom' => 'S1',
            'description' => null,
        ]);

        $template = QuestionnaireTemplate::query()->create([
            'name' => 'T risque',
            'slug' => 't-risque-'.uniqid(),
            'active' => true,
            'version' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $section = QuestionnaireSection::query()->create([
            'questionnaire_template_id' => $template->id,
            'title' => 'Risques',
            'sort_order' => 0,
        ]);

        $question = QuestionnaireQuestion::query()->create([
            'questionnaire_section_id' => $section->id,
            'code' => 'R1',
            'question' => 'Point sensible',
            'question_type' => QuestionnaireQuestion::TYPE_TEXTAREA,
            'required' => false,
            'allows_observation' => true,
            'allows_risk_detection' => true,
            'sort_order' => 0,
            'active' => true,
        ]);

        $entretien = Entretien::query()->create([
            'mission_id' => $mission->id,
            'service_id' => $service->id,
            'questionnaire_template_id' => $template->id,
            'responsable_nom' => 'X',
            'role' => null,
            'chef_hierarchique' => null,
            'auditeur' => null,
            'date_entretien' => null,
            'notes' => null,
        ]);

        $this->actingAs($user)->post(route('entretiens.dynamic-responses.store', $entretien), [
            'responses' => [
                [
                    'questionnaire_question_id' => $question->id,
                    'answer_text' => 'Anomalie constatée',
                    'identified_risk' => [
                        'title' => 'Absence de contrôle',
                        'description' => 'Détail',
                        'category' => 'Contrôle interne',
                        'criticality' => 'Moyenne',
                    ],
                ],
            ],
        ])->assertRedirect();

        $this->actingAs($user)->post(route('entretiens.dynamic-responses.store', $entretien), [
            'responses' => [
                [
                    'questionnaire_question_id' => $question->id,
                    'answer_text' => 'Anomalie constatée',
                    'identified_risk' => [
                        'title' => 'Absence de contrôle',
                        'description' => 'Détail',
                        'category' => 'Contrôle interne',
                        'criticality' => 'Moyenne',
                    ],
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('identified_risks', [
            'mission_id' => $mission->id,
            'entretien_id' => $entretien->id,
            'title' => 'Absence de contrôle',
            'criticality' => 'moyen',
        ]);

        $this->assertDatabaseCount('identified_risks', 1);
        $this->assertDatabaseHas('identified_risks', [
            'mission_id' => $mission->id,
            'entretien_id' => $entretien->id,
            'title' => 'Absence de contrôle',
            'lifecycle_status' => 'detected',
        ]);
    }
}
