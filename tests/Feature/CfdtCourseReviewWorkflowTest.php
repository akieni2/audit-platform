<?php

namespace Tests\Feature;

use App\Models\CfdtCourse;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CfdtCourseReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_sees_the_complete_qcm_in_read_only_mode(): void
    {
        [$trainer, $validator, $course] = $this->actorsAndCourse();

        $this->actingAs($trainer)->patch(route('cfdt.submit-review', $course))->assertRedirect();

        $this->actingAs($validator)->get(route('cfdt.show', $course))
            ->assertOk()
            ->assertSee('Quelle est la capitale du Gabon ?')
            ->assertSee('Libreville')
            ->assertSee('bonne réponse')
            ->assertSee('Valider et publier')
            ->assertSee('Renvoyer pour traitement')
            ->assertDontSee('Ajouter une question');
    }

    public function test_supervisor_can_return_qcm_only_with_an_observation(): void
    {
        [$trainer, $validator, $course] = $this->actorsAndCourse();
        $this->actingAs($trainer)->patch(route('cfdt.submit-review', $course));

        $this->actingAs($validator)->from(route('cfdt.show', $course))->patch(route('cfdt.review', $course), ['decision' => 'return'])
            ->assertSessionHasErrors('review_observation');

        $this->actingAs($validator)->patch(route('cfdt.review', $course), ['decision' => 'return', 'review_observation' => 'Ajouter une explication à la deuxième réponse.'])->assertRedirect();
        $this->assertDatabaseHas('cfdt_courses', ['id' => $course->id, 'status' => 'changes_requested', 'review_observation' => 'Ajouter une explication à la deuxième réponse.']);
    }

    public function test_validated_qcm_becomes_published_and_cannot_be_modified_by_supervisor(): void
    {
        [$trainer, $validator, $course] = $this->actorsAndCourse();
        $this->actingAs($trainer)->patch(route('cfdt.submit-review', $course));
        $this->actingAs($validator)->patch(route('cfdt.review', $course), ['decision' => 'approve', 'review_observation' => 'Conforme.'])->assertRedirect();

        $this->assertDatabaseHas('cfdt_courses', ['id' => $course->id, 'status' => 'published', 'validated_by' => $validator->id]);
        $this->actingAs($validator)->post(route('cfdt.questions.store', $course), $this->questionPayload())->assertForbidden();
    }

    public function test_learner_cannot_open_unvalidated_qcm(): void
    {
        [, , $course] = $this->actorsAndCourse();
        $learner = $this->user('learner');

        $this->actingAs($learner)->get(route('cfdt.show', $course))->assertForbidden();
    }

    private function actorsAndCourse(): array
    {
        $trainer = $this->user('trainer');
        $validator = $this->user('validator');
        $question = $this->questionPayload();
        $question['id'] = (string) Str::uuid();
        $course = CfdtCourse::create(['code' => 'REV-01', 'title' => 'QCM à superviser', 'questions' => [$question], 'content' => [], 'created_by' => $trainer->id]);

        return [$trainer, $validator, $course];
    }

    private function user(string $cfdtRole): User
    {
        $role = Role::firstOrCreate(['slug' => 'auditeur'], ['name' => 'Auditeur', 'hierarchy_level' => 100, 'active' => true]);

        return User::factory()->create(['role_id' => $role->id, 'cfdt_role' => $cfdtRole, 'active' => true, 'approval_status' => 'approved']);
    }

    private function questionPayload(): array
    {
        return ['text' => 'Quelle est la capitale du Gabon ?', 'type' => 'single', 'options' => ['Libreville', 'Port-Gentil'], 'correct' => [0], 'points' => 1, 'explanation' => 'Libreville est la capitale.'];
    }
}
