<?php

namespace Tests\Feature;

use App\Models\CfdtCourse;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CfdtCourseAssignmentValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_audience_redirects_to_course_with_a_useful_message(): void
    {
        [$trainer, $course] = $this->trainerAndCourse();

        $response = $this->actingAs($trainer)
            ->from(route('cfdt.show', $course))
            ->post(route('cfdt.enroll', $course), [
                'expires_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            ]);

        $response->assertRedirect(route('cfdt.show', $course));
        $response->assertSessionHasErrors([
            'audience' => 'Sélectionnez au moins un agent, une structure ou une fonction.',
        ]);
    }

    public function test_audience_without_active_agents_redirects_with_a_useful_message(): void
    {
        [$trainer, $course] = $this->trainerAndCourse();
        $department = Department::create([
            'name' => 'Structure sans agent',
            'code' => 'SSA',
            'type' => 'service',
            'active' => true,
        ]);

        $response = $this->actingAs($trainer)
            ->from(route('cfdt.show', $course))
            ->post(route('cfdt.enroll', $course), [
                'department_ids' => [$department->id],
                'expires_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            ]);

        $response->assertRedirect(route('cfdt.show', $course));
        $response->assertSessionHasErrors([
            'audience' => 'Aucun agent actif ne correspond aux critères sélectionnés.',
        ]);
    }

    public function test_assignment_form_explains_the_required_audience(): void
    {
        [$trainer, $course] = $this->trainerAndCourse();

        $this->actingAs($trainer)
            ->get(route('cfdt.show', $course))
            ->assertOk()
            ->assertSee('Sélectionnez au moins un agent, une structure ou une catégorie professionnelle.')
            ->assertSee('Affecter et notifier');
    }

    private function trainerAndCourse(): array
    {
        $role = Role::firstOrCreate(
            ['slug' => 'auditeur'],
            ['name' => 'Auditeur', 'hierarchy_level' => 100, 'active' => true],
        );
        $trainer = User::factory()->create([
            'role_id' => $role->id,
            'cfdt_role' => 'trainer',
            'active' => true,
            'approval_status' => 'approved',
        ]);
        $course = CfdtCourse::create([
            'code' => 'VALIDATION-01',
            'title' => 'Validation des affectations',
            'questions' => [],
            'content' => [],
            'created_by' => $trainer->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        return [$trainer, $course];
    }
}
