<?php

namespace Tests\Feature\Missions;

use App\Models\Department;
use App\Models\Mission;
use App\Models\QuestionnaireSection;
use App\Models\QuestionnaireTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissionQuestionnaireWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_mission_supervisor_creates_a_published_questionnaire_with_visual_hierarchy(): void
    {
        [$mission, $supervisor] = $this->missionWithSupervisor();
        $structure = [
            'theme' => 'ALIGNEMENT STRATÉGIQUE',
            'thematics' => [[
                'title' => 'Alignement SDSI',
                'subthemes' => [[
                    'title' => 'Vision et stratégie',
                    'questions' => [[
                        'question' => 'La vision SI est-elle formalisée ?',
                        'question_type' => 'boolean_na',
                        'expected_documents' => 'SDSI validé',
                        'help_text' => 'Vérifier la date de validation.',
                        'required' => true,
                        'allows_observation' => true,
                        'allows_risk_detection' => true,
                    ]],
                ]],
            ]],
        ];

        $this->actingAs($supervisor)
            ->post(route('missions.questionnaires.wizard.store', $mission), [
                'structure' => json_encode($structure, JSON_THROW_ON_ERROR),
            ])
            ->assertRedirect(route('missions.show', $mission));

        $template = QuestionnaireTemplate::query()->where('mission_id', $mission->id)->firstOrFail();
        $this->assertSame(QuestionnaireTemplate::STATUS_PUBLISHED, $template->lifecycle_status);
        $this->assertTrue($template->active);
        $this->assertSame([$mission->department_id], $template->department_scope);
        $this->assertTrue($supervisor->can('update', $template));
        $this->assertDatabaseHas('questionnaire_sections', [
            'questionnaire_template_id' => $template->id,
            'title' => 'ALIGNEMENT STRATÉGIQUE',
            'section_type' => QuestionnaireSection::TYPE_THEME,
        ]);
        $this->assertDatabaseHas('questionnaire_sections', [
            'questionnaire_template_id' => $template->id,
            'title' => 'Vision et stratégie',
            'section_type' => QuestionnaireSection::TYPE_SUBTHEME,
        ]);
        $this->assertDatabaseHas('questionnaire_questions', [
            'question' => 'La vision SI est-elle formalisée ?',
            'expected_documents' => 'SDSI validé',
        ]);
    }

    public function test_normal_agent_cannot_open_or_submit_the_mission_wizard(): void
    {
        [$mission] = $this->missionWithSupervisor();
        $agent = User::factory()->create([
            'department_id' => $mission->department_id,
            'approval_status' => 'approved',
            'active' => true,
        ]);

        $this->actingAs($agent)
            ->get(route('missions.questionnaires.wizard.create', $mission))
            ->assertForbidden();
        $this->actingAs($agent)
            ->post(route('missions.questionnaires.wizard.store', $mission), ['structure' => '{}'])
            ->assertForbidden();
    }

    public function test_mission_questionnaire_is_read_only_for_a_normal_agent(): void
    {
        [$mission] = $this->missionWithSupervisor();
        $agent = User::factory()->create([
            'department_id' => $mission->department_id,
            'approval_status' => 'approved',
            'active' => true,
        ]);
        $template = QuestionnaireTemplate::query()->create([
            'name' => 'Questionnaire de mission',
            'slug' => 'questionnaire-mission-read-only',
            'mission_id' => $mission->id,
            'active' => true,
            'lifecycle_status' => QuestionnaireTemplate::STATUS_PUBLISHED,
        ]);

        $this->assertTrue($agent->can('view', $template));
        $this->assertFalse($agent->can('update', $template));
        $this->assertFalse($agent->can('delete', $template));
    }

    /** @return array{Mission, User} */
    private function missionWithSupervisor(): array
    {
        $department = Department::query()->create([
            'name' => 'Pôle Informatique',
            'code' => 'PI',
            'type' => 'pole',
            'active' => true,
        ]);
        $supervisor = User::factory()->create([
            'department_id' => $department->id,
            'approval_status' => 'approved',
            'active' => true,
        ]);
        $department->update(['supervisor_user_id' => $supervisor->id]);
        $mission = Mission::query()->create([
            'organisation' => 'Audit du management DSI',
            'date_debut' => now(),
            'auditeur_id' => $supervisor->id,
            'department_id' => $department->id,
            'mission_status' => Mission::STATUS_BROUILLON,
        ]);

        return [$mission, $supervisor];
    }
}
