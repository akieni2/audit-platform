<?php

namespace Tests\Feature;

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

class QuestionnaireBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_builder_can_create_template_section_question_and_reorder(): void
    {
        $user = $this->inspecteurNational();
        $this->actingAs($user);

        $this->post(route('questionnaire-builder.templates.store'), [
            'name' => 'Builder Template',
            'description' => 'Template enterprise',
            'mission_type' => 'audit_si',
        ])->assertRedirect();

        $template = QuestionnaireTemplate::query()->where('name', 'Builder Template')->firstOrFail();

        $this->assertDatabaseHas('questionnaire_templates', [
            'id' => $template->id,
            'lifecycle_status' => QuestionnaireTemplate::STATUS_DRAFT,
            'active' => false,
        ]);

        $this->post(route('questionnaire-builder.sections.store', $template), [
            'title' => 'Gouvernance',
            'description' => 'Section A',
            'sort_order' => 0,
        ])->assertRedirect(route('questionnaire-builder.edit', $template));

        $this->post(route('questionnaire-builder.sections.store', $template), [
            'title' => 'Contrôles',
            'description' => 'Section B',
            'sort_order' => 1,
        ])->assertRedirect(route('questionnaire-builder.edit', $template));

        $sectionA = QuestionnaireSection::query()->where('questionnaire_template_id', $template->id)->where('title', 'Gouvernance')->firstOrFail();
        $sectionB = QuestionnaireSection::query()->where('questionnaire_template_id', $template->id)->where('title', 'Contrôles')->firstOrFail();

        $this->post(route('questionnaire-builder.questions.store', $sectionA), [
            'code' => 'Q-GOV-01',
            'question' => 'Le dispositif est-il formalisé ?',
            'question_type' => QuestionnaireQuestion::TYPE_SELECT,
            'options_text' => "Oui\nNon\nPartiel",
            'required' => '1',
            'allows_observation' => '1',
            'risk_mapping_enabled' => '1',
            'risk_category' => 'Sécurité SI',
            'risk_level' => 'eleve',
            'documents_required' => '1',
            'documents_list_text' => "Politique sécurité\nJournal accès",
            'scoring_enabled' => '1',
            'scoring_weight' => 5,
            'sort_order' => 0,
        ])->assertRedirect(route('questionnaire-builder.edit', $template));

        $this->post(route('questionnaire-builder.questions.store', $sectionA), [
            'code' => 'Q-GOV-02',
            'question' => 'Date de dernière revue',
            'question_type' => QuestionnaireQuestion::TYPE_DATE,
            'allows_observation' => '1',
            'sort_order' => 1,
        ])->assertRedirect(route('questionnaire-builder.edit', $template));

        $firstQuestion = QuestionnaireQuestion::query()->where('questionnaire_section_id', $sectionA->id)->where('code', 'Q-GOV-01')->firstOrFail();
        $secondQuestion = QuestionnaireQuestion::query()->where('questionnaire_section_id', $sectionA->id)->where('code', 'Q-GOV-02')->firstOrFail();

        $this->post(route('questionnaire-builder.sections.reorder'), [
            'template_id' => $template->id,
            'positions' => [
                $sectionA->id => 1,
                $sectionB->id => 0,
            ],
        ])->assertRedirect(route('questionnaire-builder.edit', $template));

        $this->post(route('questionnaire-builder.questions.reorder'), [
            'section_id' => $sectionA->id,
            'positions' => [
                $firstQuestion->id => 1,
                $secondQuestion->id => 0,
            ],
        ])->assertRedirect(route('questionnaire-builder.edit', $template));

        $this->assertDatabaseHas('questionnaire_sections', [
            'id' => $sectionB->id,
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('questionnaire_questions', [
            'id' => $secondQuestion->id,
            'sort_order' => 0,
            'risk_level' => null,
        ]);
        $this->assertDatabaseHas('questionnaire_questions', [
            'id' => $firstQuestion->id,
            'sort_order' => 1,
            'risk_level' => 'eleve',
        ]);
    }

    public function test_builder_publishing_and_versioning_keep_published_templates_immutable(): void
    {
        $user = $this->inspecteurNational();
        $this->actingAs($user);

        $template = $this->draftTemplateWithQuestion($user, 'Publication Builder');

        $this->post(route('questionnaire-builder.templates.publish', $template))
            ->assertRedirect(route('questionnaire-builder.edit', $template));

        $template->refresh();

        $this->assertSame(QuestionnaireTemplate::STATUS_PUBLISHED, $template->lifecycle_status);
        $this->assertTrue($template->active);
        $this->assertNotNull($template->signature_hash);
        $this->assertSame(1, $template->version);

        $this->patch(route('questionnaire-builder.templates.update', $template), [
            'name' => 'Publication Builder v2',
            'slug' => $template->slug.'-draft-next',
            'description' => 'Nouvelle version',
            'mission_type' => 'audit_si',
            'department_scope' => [],
        ])->assertRedirect();

        $draft = QuestionnaireTemplate::query()
            ->where('name', 'Publication Builder v2')
            ->where('lifecycle_status', QuestionnaireTemplate::STATUS_DRAFT)
            ->latest('id')
            ->firstOrFail();

        $this->assertNotSame($template->id, $draft->id);
        $this->assertSame($template->id, $draft->source_template_id);
        $this->assertDatabaseHas('questionnaire_sections', [
            'questionnaire_template_id' => $draft->id,
            'source_section_id' => $template->sections()->firstOrFail()->id,
        ]);

        $this->post(route('questionnaire-builder.templates.publish', $draft))
            ->assertRedirect(route('questionnaire-builder.edit', $draft));

        $template->refresh();
        $draft->refresh();

        $this->assertSame(QuestionnaireTemplate::STATUS_DEPRECATED, $template->lifecycle_status);
        $this->assertFalse($template->active);
        $this->assertSame(QuestionnaireTemplate::STATUS_PUBLISHED, $draft->lifecycle_status);
        $this->assertTrue($draft->active);
        $this->assertSame(2, $draft->version);
    }

    public function test_published_builder_template_remains_runtime_compatible_via_snapshot(): void
    {
        $user = $this->inspecteurNational();
        $department = $this->department();
        $this->actingAs($user);

        $template = $this->draftTemplateWithQuestion($user, 'Runtime Compat');

        $this->post(route('questionnaire-builder.templates.publish', $template))
            ->assertRedirect(route('questionnaire-builder.edit', $template));

        $template->refresh();

        $mission = Mission::query()->create([
            'organisation' => 'Org runtime builder',
            'description' => 'Compatibilité builder',
            'date_debut' => Carbon::today(),
            'date_fin' => null,
            'auditeur_id' => $user->id,
            'department_id' => $department->id,
            'mission_status' => Mission::STATUS_BROUILLON,
        ]);

        $service = Service::query()->create([
            'mission_id' => $mission->id,
            'nom' => 'Service Runtime',
            'description' => null,
        ]);

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
        $this->assertDatabaseHas('questionnaire_templates', [
            'id' => $template->id,
            'lifecycle_status' => QuestionnaireTemplate::STATUS_PUBLISHED,
            'active' => true,
        ]);
    }

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

    private function draftTemplateWithQuestion(User $user, string $name): QuestionnaireTemplate
    {
        $this->post(route('questionnaire-builder.templates.store'), [
            'name' => $name,
            'mission_type' => 'audit_si',
        ])->assertRedirect();

        $template = QuestionnaireTemplate::query()->where('name', $name)->firstOrFail();

        $this->post(route('questionnaire-builder.sections.store', $template), [
            'title' => 'Section 1',
            'description' => 'Structure builder',
            'sort_order' => 0,
        ])->assertRedirect(route('questionnaire-builder.edit', $template));

        $section = QuestionnaireSection::query()->where('questionnaire_template_id', $template->id)->firstOrFail();

        $this->post(route('questionnaire-builder.questions.store', $section), [
            'code' => 'QB-01',
            'question' => 'Question builder',
            'question_type' => QuestionnaireQuestion::TYPE_BOOLEAN_NA,
            'allows_observation' => '1',
            'sort_order' => 0,
        ])->assertRedirect(route('questionnaire-builder.edit', $template));

        return $template->fresh();
    }
}
