<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkflowStage;
use App\Models\WorkflowTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_builder_can_create_stages_transitions_publish_and_clone_new_draft(): void
    {
        $user = $this->inspecteurNational();
        $department = $this->department();
        $this->actingAs($user);

        $this->post(route('workflow-builder.store'), [
            'name' => 'Workflow Départemental',
            'slug' => 'workflow-departemental',
            'code' => 'WF_DEPT',
            'department_id' => $department->id,
            'description' => 'Workflow configurable',
        ])->assertRedirect();

        $template = WorkflowTemplate::query()->where('name', 'Workflow Départemental')->firstOrFail();

        $this->assertDatabaseHas('workflow_templates', [
            'id' => $template->id,
            'status' => WorkflowTemplate::STATUS_DRAFT,
            'active' => false,
            'department_id' => $department->id,
        ]);

        $this->post(route('workflow-builder.stages.store', $template), [
            'name' => 'Mission',
            'code' => 'MISSION',
            'stage_type' => 'mission',
            'execution_mode' => 'automatic',
            'sort_order' => 0,
            'position_x' => 0,
            'position_y' => 0,
            'is_required' => '1',
        ])->assertRedirect();

        $this->post(route('workflow-builder.stages.store', $template), [
            'name' => 'Reporting',
            'code' => 'REPORTING',
            'stage_type' => 'reporting',
            'execution_mode' => 'manual',
            'sort_order' => 1,
            'position_x' => 240,
            'position_y' => 0,
            'is_required' => '1',
        ])->assertRedirect();

        $missionStage = WorkflowStage::query()->where('workflow_template_id', $template->id)->where('code', 'MISSION')->firstOrFail();
        $reportingStage = WorkflowStage::query()->where('workflow_template_id', $template->id)->where('code', 'REPORTING')->firstOrFail();

        $this->post(route('workflow-builder.transitions.store', $template), [
            'from_stage_id' => $missionStage->id,
            'to_stage_id' => $reportingStage->id,
            'is_automatic' => '1',
        ])->assertRedirect(route('workflow-builder.edit', $template));

        $this->post(route('workflow-builder.publish', $template))
            ->assertRedirect(route('workflow-builder.edit', $template));

        $template->refresh();
        $this->assertSame(WorkflowTemplate::STATUS_PUBLISHED, $template->status?->value ?? $template->status);
        $this->assertTrue($template->active);
        $this->assertNotNull($template->signature_hash);

        $this->patch(route('workflow-builder.update', $template), [
            'name' => 'Workflow Départemental v2',
            'slug' => 'workflow-departemental-v2',
            'code' => 'WF_DEPT',
            'department_id' => $department->id,
            'description' => 'Nouvelle version',
        ])->assertRedirect();

        $draft = WorkflowTemplate::query()
            ->where('name', 'Workflow Départemental v2')
            ->where('status', WorkflowTemplate::STATUS_DRAFT)
            ->latest('id')
            ->firstOrFail();

        $this->assertNotSame($template->id, $draft->id);
        $this->assertSame($template->id, $draft->source_template_id);
        $this->assertDatabaseHas('workflow_stages', [
            'workflow_template_id' => $draft->id,
            'code' => 'MISSION',
        ]);
        $this->assertDatabaseHas('workflow_transitions', [
            'workflow_template_id' => $draft->id,
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
            'name' => 'Pôle Workflow',
            'code' => 'WFLOW',
            'type' => 'pole',
            'description' => 'Workflow tests',
            'active' => true,
        ]);
    }
}
