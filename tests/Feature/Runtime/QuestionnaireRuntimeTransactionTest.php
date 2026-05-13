<?php

namespace Tests\Feature\Runtime;

use App\Models\Department;
use App\Models\Entretien;
use App\Models\Mission;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireSection;
use App\Models\QuestionnaireTemplate;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Services\Questionnaires\QuestionnaireRuntimeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Tests\TestCase;

class QuestionnaireRuntimeTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rolls_back_response_batch_when_one_question_is_invalid(): void
    {
        [$user, $entretien, $question] = $this->runtimeContext();

        $request = Request::create('/runtime-test', 'POST');
        $request->setUserResolver(fn () => $user);

        try {
            app(QuestionnaireRuntimeService::class)->recordResponses(
                entretien: $entretien,
                rows: [
                    [
                        'questionnaire_question_id' => $question->id,
                        'answer_text' => 'Réponse valide',
                    ],
                    [
                        'questionnaire_question_id' => 999999,
                        'answer_text' => 'Réponse invalide',
                    ],
                ],
                user: $user,
                request: $request,
            );

            $this->fail('The response batch should have been rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('Question hors modèle', $exception->getMessage());
        }

        $this->assertDatabaseCount('entretien_responses', 0);
        $this->assertDatabaseCount('identified_risks', 0);
    }

    /**
     * @return array{User, Entretien, QuestionnaireQuestion}
     */
    private function runtimeContext(): array
    {
        $department = Department::query()->create([
            'name' => 'Pôle Runtime',
            'code' => 'RUN',
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
            'organisation' => 'Org runtime',
            'description' => 'Test runtime',
            'date_debut' => now()->toDateString(),
            'auditeur_id' => $user->id,
            'department_id' => $department->id,
            'mission_status' => Mission::STATUS_EN_COURS,
        ]);

        $service = Service::query()->create([
            'mission_id' => $mission->id,
            'nom' => 'Service Runtime',
        ]);

        $template = QuestionnaireTemplate::query()->create([
            'name' => 'Template Runtime',
            'slug' => 'template-runtime',
            'active' => true,
            'version' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $section = QuestionnaireSection::query()->create([
            'questionnaire_template_id' => $template->id,
            'title' => 'Section 1',
            'sort_order' => 1,
        ]);

        $question = QuestionnaireQuestion::query()->create([
            'questionnaire_section_id' => $section->id,
            'code' => 'Q-1',
            'question' => 'La pièce existe-t-elle ?',
            'question_type' => QuestionnaireQuestion::TYPE_TEXTAREA,
            'required' => false,
            'allows_observation' => false,
            'allows_risk_detection' => false,
            'sort_order' => 1,
            'active' => true,
        ]);

        $entretien = Entretien::query()->create([
            'mission_id' => $mission->id,
            'service_id' => $service->id,
            'questionnaire_template_id' => $template->id,
            'status' => Entretien::STATUS_DRAFT,
        ]);

        app(QuestionnaireRuntimeService::class)->ensureSnapshot($entretien);

        return [$user, $entretien->fresh(), $question];
    }
}
